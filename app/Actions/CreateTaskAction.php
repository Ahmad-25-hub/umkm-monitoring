<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\Task;
use App\Models\TaskAssignee;
use App\Models\User;
use App\TaskType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateTaskAction
{
    public function __construct(private GenerateTaskOccurrencesAction $generateOccurrences) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Business $business, User $creator, array $data): Task
    {
        return DB::transaction(function () use ($business, $creator, $data): Task {
            $assigneeIds = $data['assignee_ids'];
            $attributes = Arr::except($data, ['assignee_ids']);
            $attributes['business_id'] = $business->id;
            $attributes['created_by_user_id'] = $creator->id;
            $this->normalizeSchedule($attributes);

            $task = Task::query()->create($attributes);
            $now = now();
            TaskAssignee::query()->insert(array_map(
                fn (int $userId): array => [
                    'task_id' => $task->id,
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $assigneeIds,
            ));

            $task->load('assignees:id');
            $generationDate = $task->type === TaskType::OneTime ? $task->starts_on : today();
            $this->generateOccurrences->executeForTask($task, $generationDate);

            return $task;
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
