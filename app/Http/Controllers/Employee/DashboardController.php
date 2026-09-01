<?php

namespace App\Http\Controllers\Employee;

use App\Actions\BuildSalesDashboardStatisticsAction;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\TaskOccurrence;
use App\Models\User;
use App\TaskOccurrenceStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BuildSalesDashboardStatisticsAction $buildSalesStatistics): View
    {
        /** @var User $employee */
        $employee = $request->user();
        /** @var Business $business */
        $business = $request->attributes->get('activeEmployeeBusiness');

        $initials = Str::of($employee->name)
            ->squish()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        $occurrences = TaskOccurrence::query()
            ->whereBelongsTo($employee, 'assignee')
            ->whereHas('task', fn ($query) => $query->whereBelongsTo($business))
            ->where('status', '!=', TaskOccurrenceStatus::Cancelled->value)
            ->where(function ($query): void {
                $query
                    ->where('status', '!=', TaskOccurrenceStatus::Completed->value)
                    ->orWhere('completed_at', '>=', now()->subDays(7));
            })
            ->with(['task:id,business_id,created_by_user_id,type', 'task.creator:id,name'])
            ->orderByRaw('CASE WHEN due_at < ? AND status NOT IN (?, ?) THEN 0 ELSE 1 END', [
                now(),
                TaskOccurrenceStatus::Completed->value,
                TaskOccurrenceStatus::Cancelled->value,
            ])
            ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END")
            ->orderBy('due_at')
            ->limit(100)
            ->get();

        $taskGroups = [
            'new' => $occurrences->filter(fn (TaskOccurrence $occurrence): bool => $occurrence->status === TaskOccurrenceStatus::Pending && $occurrence->viewed_at === null && ! $occurrence->isOverdue()),
            'today' => $occurrences->filter(fn (TaskOccurrence $occurrence): bool => $occurrence->status === TaskOccurrenceStatus::Pending && $occurrence->viewed_at !== null && $occurrence->occurrence_date->isToday() && ! $occurrence->isOverdue()),
            'in_progress' => $occurrences->filter(fn (TaskOccurrence $occurrence): bool => $occurrence->status === TaskOccurrenceStatus::InProgress && ! $occurrence->isOverdue()),
            'overdue' => $occurrences->filter(fn (TaskOccurrence $occurrence): bool => $occurrence->isOverdue()),
            'completed' => $occurrences->filter(fn (TaskOccurrence $occurrence): bool => $occurrence->status === TaskOccurrenceStatus::Completed),
        ];

        TaskOccurrence::query()
            ->whereIn('id', $occurrences->whereNull('viewed_at')->pluck('id'))
            ->update(['viewed_at' => now()]);

        $salesStatistics = $buildSalesStatistics->execute($business);

        return view('employee.dashboard', [
            'employee' => [
                'name' => $employee->name,
                'email' => $employee->email,
                'initials' => $initials,
            ],
            'business' => $business,
            'taskGroups' => $taskGroups,
            'salesStatistics' => $salesStatistics,
        ]);
    }
}
