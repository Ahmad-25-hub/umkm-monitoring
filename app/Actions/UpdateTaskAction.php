<?php

namespace App\Actions;

use App\Models\Task;
use App\Models\TaskOccurrence;
use App\TaskOccurrenceStatus;
use App\TaskType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateTaskAction
{
    public function __construct(private GenerateTaskOccurrencesAction $generateOccurrences) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data): Task {
            /** @var Task $lockedTask */
            $lockedTask = Task::query()->lockForUpdate()->findOrFail($task->id);
            $assigneeIds = array_map('intval', $data['assignee_ids']);
            $attributes = Arr::except($data, ['assignee_ids']);
            $this->normalizeSchedule($attributes);

            $lockedTask->update($attributes);
            $lockedTask->assignees()->sync($assigneeIds);
            $lockedTask->refresh()->load('assignees:id');

            TaskOccurrence::query()
                ->whereBelongsTo($lockedTask)
                ->where('status', TaskOccurrenceStatus::Pending->value)
                ->lazyById()
                ->each(function (TaskOccurrence $occurrence) use ($lockedTask, $assigneeIds): void {
                    $isStillAssigned = in_array($occurrence->user_id, $assigneeIds, true);
                    $isStillScheduled = $this->generateOccurrences->isScheduledFor(
                        $lockedTask,
                        $occurrence->occurrence_date,
                    );

                    if (! $lockedTask->is_active || ! $isStillAssigned || ! $isStillScheduled) {
                        $occurrence->update(['status' => TaskOccurrenceStatus::Cancelled]);

                        return;
                    }

                    $occurrence->update([
                        'due_at' => $this->generateOccurrences->dueAtFor($lockedTask, $occurrence->occurrence_date),
                        'title' => $lockedTask->title,
                        'description' => $lockedTask->description,
                        'priority' => $lockedTask->priority,
                    ]);
                });

            if ($lockedTask->is_active) {
                $generationDate = $lockedTask->type === TaskType::OneTime
                    ? $lockedTask->starts_on
                    : today();
                $this->generateOccurrences->executeForTask($lockedTask, $generationDate);
            }

            return $lockedTask;
        });
    }

    /** @param array<string, mixed> $attributes */
    private function normalizeSchedule(array &$attributes): void
    {
        if ($attributes['type'] === TaskType::OneTime->value) {
            $attributes['ends_on'] = null;
            $attributes['daily_due_time'] = null;

            return;
        }

        $attributes['due_at'] = null;
    }
}
