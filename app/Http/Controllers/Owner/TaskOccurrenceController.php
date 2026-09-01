<?php

namespace App\Http\Controllers\Owner;

use App\Actions\BuildOwnerNavigationContextAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\MonitorTaskOccurrencesRequest;
use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\TaskOccurrence;
use App\TaskOccurrenceStatus;
use App\TaskPriority;
use Illuminate\Contracts\View\View;

class TaskOccurrenceController extends Controller
{
    public function __invoke(MonitorTaskOccurrencesRequest $request, BuildOwnerNavigationContextAction $navigationContext): View
    {
        /** @var Business $business */
        $business = $request->attributes->get('activeBusiness');
        $filters = $request->validated();
        $query = TaskOccurrence::query()
            ->whereHas('task', fn ($query) => $query->whereBelongsTo($business))
            ->with(['assignee:id,name,email', 'task:id,business_id,type']);

        if (! empty($filters['date'])) {
            $query->whereDate('occurrence_date', $filters['date']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('user_id', $filters['employee_id']);
        }

        if (! empty($filters['status'])) {
            $filters['status'] === 'overdue'
                ? $query->overdue()
                : $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        $occurrences = $query
            ->orderByRaw('CASE WHEN due_at < ? AND status NOT IN (?, ?) THEN 0 ELSE 1 END', [
                now(),
                TaskOccurrenceStatus::Completed->value,
                TaskOccurrenceStatus::Cancelled->value,
            ])
            ->orderBy('due_at')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $summary = TaskOccurrence::query()
            ->whereHas('task', fn ($query) => $query->whereBelongsTo($business))
            ->toBase()
            ->selectRaw('SUM(CASE WHEN status = ? AND due_at >= ? THEN 1 ELSE 0 END) as pending_count', [TaskOccurrenceStatus::Pending->value, now()])
            ->selectRaw('SUM(CASE WHEN status = ? AND due_at >= ? THEN 1 ELSE 0 END) as in_progress_count', [TaskOccurrenceStatus::InProgress->value, now()])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_count', [TaskOccurrenceStatus::Completed->value])
            ->selectRaw('SUM(CASE WHEN due_at < ? AND status NOT IN (?, ?) THEN 1 ELSE 0 END) as overdue_count', [now(), TaskOccurrenceStatus::Completed->value, TaskOccurrenceStatus::Cancelled->value])
            ->first();

        $employees = $business->memberships()
            ->where('role', BusinessMembership::ROLE_EMPLOYEE)
            ->with('user:id,name')
            ->oldest('id')
            ->get();

        return view('owner.task-occurrences.index', [
            ...$navigationContext->execute($request),
            'occurrences' => $occurrences,
            'summary' => $summary,
            'employees' => $employees,
            'statuses' => TaskOccurrenceStatus::cases(),
            'priorities' => TaskPriority::cases(),
            'filters' => $filters,
        ]);
    }
}
