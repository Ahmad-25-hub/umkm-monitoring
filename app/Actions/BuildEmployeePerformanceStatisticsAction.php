<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\Task;
use App\Models\TaskOccurrence;
use App\TaskOccurrenceStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class BuildEmployeePerformanceStatisticsAction
{
    public const MINIMUM_RATE_SAMPLE = 5;

    public function __construct(private GenerateTaskOccurrencesAction $generateOccurrences) {}

    /**
     * Cohorts use occurrence dates in WIB. Completion events are UTC; deadlines are WIB wall-clock values.
     *
     * @return list<array{name: string, user_id: int, assigned: int, assessed: int, completed: int, on_time: int, late_completed: int, overdue: int, upcoming: int, missing_completion_time: int, completion_rate: ?float, on_time_rate: ?float}>
     */
    public function execute(Business $business, string $start, string $end, string $employee = ''): array
    {
        $now = CarbonImmutable::now(Task::TIMEZONE);

        if ($start <= $now->toDateString() && $end >= $now->toDateString()) {
            $this->generateOccurrences->execute($now->toDateString(), business: $business);
        }

        $query = TaskOccurrence::query()
            ->whereHas('task', fn (Builder $query): Builder => $query->whereBelongsTo($business))
            ->whereBetween('occurrence_date', [$start.' 00:00:00', $end.' 23:59:59'])
            ->where('status', '!=', TaskOccurrenceStatus::Cancelled->value)
            ->with('assignee:id,name');

        $people = [];

        foreach ($query->select(['id', 'user_id', 'status', 'due_at', 'completed_at'])->lazyById(500) as $occurrence) {
            $name = $occurrence->assignee->name;

            if ($employee !== '' && ! str_contains(mb_strtolower($name), mb_strtolower($employee))) {
                continue;
            }

            $person = $people[$occurrence->user_id] ?? [
                'name' => $name, 'user_id' => $occurrence->user_id,
                'assigned' => 0, 'assessed' => 0, 'completed' => 0, 'on_time' => 0,
                'late_completed' => 0, 'overdue' => 0, 'upcoming' => 0, 'missing_completion_time' => 0,
            ];
            $person['assigned']++;
            $due = $occurrence->due_at->copy()->shiftTimezone(Task::TIMEZONE);
            $completed = $occurrence->status === TaskOccurrenceStatus::Completed;

            if ($completed || $due->lte($now)) {
                $person['assessed']++;
            } else {
                $person['upcoming']++;
            }

            if ($completed) {
                $person['completed']++;

                if ($occurrence->completed_at === null) {
                    $person['missing_completion_time']++;
                } elseif ($occurrence->completed_at->copy()->setTimezone(Task::TIMEZONE)->lte($due)) {
                    $person['on_time']++;
                } else {
                    $person['late_completed']++;
                }
            } elseif ($due->lt($now)) {
                $person['overdue']++;
            }

            $people[$occurrence->user_id] = $person;
        }

        return array_values(array_map(fn (array $person): array => [
            ...$person,
            'completion_rate' => $person['assessed'] > 0 ? $person['completed'] / $person['assessed'] * 100 : null,
            'on_time_rate' => $person['assessed'] > 0 && $person['missing_completion_time'] === 0
                ? $person['on_time'] / $person['assessed'] * 100 : null,
        ], $people));
    }
}
