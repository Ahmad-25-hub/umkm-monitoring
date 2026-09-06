<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use App\TaskOccurrenceStatus;
use App\TaskType;
use Carbon\CarbonImmutable;
use DateTimeInterface;

class GenerateTaskOccurrencesAction
{
    public function execute(DateTimeInterface|string|null $date = null, ?Business $business = null, ?User $assignee = null): int
    {
        $occurrenceDate = $this->occurrenceDate($date);
        $createdCount = 0;

        Task::query()
            ->when($business !== null, fn ($query) => $query->whereBelongsTo($business))
            ->when($assignee !== null, fn ($query) => $query->whereHas('assignees', fn ($query) => $query->whereKey($assignee->id)))
            ->where('type', TaskType::Daily->value)
            ->where('is_active', true)
            ->whereDate('starts_on', '<=', $occurrenceDate)
            ->where(function ($query) use ($occurrenceDate): void {
                $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $occurrenceDate);
            })
            ->with(['assignees' => fn ($query) => $query->select('users.id')->when($assignee !== null, fn ($query) => $query->whereKey($assignee->id))])
            ->chunkById(100, function ($tasks) use ($occurrenceDate, &$createdCount): void {
                foreach ($tasks as $task) {
                    $createdCount += $this->executeForTask($task, $occurrenceDate);
                }
            });

        return $createdCount;
    }

    public function executeForTask(Task $task, DateTimeInterface|string $date): int
    {
        $occurrenceDate = $this->occurrenceDate($date);

        if (! $task->is_active || ! $this->isScheduledFor($task, $occurrenceDate)) {
            return 0;
        }

        $task->loadMissing('assignees:id');
        $now = now();
        $rows = $task->assignees->map(fn ($assignee): array => [
            'task_id' => $task->id,
            'user_id' => $assignee->id,
            'occurrence_date' => $occurrenceDate->toDateString(),
            'due_at' => $this->dueAtFor($task, $occurrenceDate)->format('Y-m-d H:i:s'),
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority->value,
            'status' => TaskOccurrenceStatus::Pending->value,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        return $rows === [] ? 0 : TaskOccurrence::query()->insertOrIgnore($rows);
    }

    public function isScheduledFor(Task $task, DateTimeInterface|string $date): bool
    {
        $occurrenceDate = $this->occurrenceDate($date);

        if ($task->type === TaskType::OneTime) {
            return $task->starts_on->isSameDay($occurrenceDate);
        }

        return $occurrenceDate->toDateString() >= $task->starts_on->toDateString()
            && ($task->ends_on === null || $occurrenceDate->toDateString() <= $task->ends_on->toDateString());
    }

    private function occurrenceDate(DateTimeInterface|string|null $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date ?? 'now', Task::TIMEZONE)
            ->setTimezone(Task::TIMEZONE)
            ->startOfDay();
    }

    public function dueAtFor(Task $task, DateTimeInterface|string $date): CarbonImmutable
    {
        if ($task->type === TaskType::OneTime) {
            return CarbonImmutable::instance($task->due_at);
        }

        $occurrenceDate = $this->occurrenceDate($date);

        return CarbonImmutable::parse(
            $occurrenceDate->toDateString().' '.$task->daily_due_time,
            Task::TIMEZONE,
        );
    }
}
