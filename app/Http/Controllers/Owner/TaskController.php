<?php

namespace App\Http\Controllers\Owner;

use App\Actions\BuildOwnerNavigationContextAction;
use App\Actions\CreateTaskAction;
use App\Actions\UpdateTaskAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreTaskRequest;
use App\Http\Requests\Owner\UpdateTaskRequest;
use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\Task;
use App\TaskOccurrenceStatus;
use App\TaskPriority;
use App\TaskType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function index(Request $request, BuildOwnerNavigationContextAction $navigationContext): View
    {
        /** @var Business $business */
        $business = $request->attributes->get('activeBusiness');
        Gate::authorize('createForBusiness', [Task::class, $business]);

        $tasks = Task::query()
            ->whereBelongsTo($business)
            ->with('assignees:id,name')
            ->withCount([
                'occurrences as pending_occurrences_count' => fn ($query) => $query->whereIn('status', [
                    TaskOccurrenceStatus::Pending->value,
                    TaskOccurrenceStatus::InProgress->value,
                ]),
                'occurrences as completed_occurrences_count' => fn ($query) => $query->where('status', TaskOccurrenceStatus::Completed->value),
            ])
            ->latest('id')
            ->paginate(12);

        return view('owner.tasks.index', [
            ...$navigationContext->execute($request),
            'tasks' => $tasks,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, BuildOwnerNavigationContextAction $navigationContext): View
    {
        /** @var Business $business */
        $business = $request->attributes->get('activeBusiness');
        Gate::authorize('createForBusiness', [Task::class, $business]);

        return view('owner.tasks.create', [
            ...$navigationContext->execute($request),
            'employees' => $this->employeesFor($business),
            'types' => TaskType::cases(),
            'priorities' => TaskPriority::cases(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request, CreateTaskAction $createTask): RedirectResponse
    {
        /** @var Business $business */
        $business = $request->attributes->get('activeBusiness');
        $createTask->execute($business, $request->user(), $request->validated());

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function edit(Request $request, Task $task, BuildOwnerNavigationContextAction $navigationContext): View
    {
        Gate::authorize('update', $task);
        /** @var Business $business */
        $business = $request->attributes->get('activeBusiness');

        return view('owner.tasks.edit', [
            ...$navigationContext->execute($request),
            'task' => $task->load('assignees:id'),
            'employees' => $this->employeesFor($business),
            'types' => TaskType::cases(),
            'priorities' => TaskPriority::cases(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task, UpdateTaskAction $updateTask): RedirectResponse
    {
        $updateTask->execute($task, $request->validated());

        return redirect()->route('tasks.index')->with('success', 'Tugas berhasil diperbarui.');
    }

    /**
     * @return Collection<int, BusinessMembership>
     */
    private function employeesFor(Business $business)
    {
        return $business->memberships()
            ->where('role', BusinessMembership::ROLE_EMPLOYEE)
            ->active()
            ->with('user:id,name,email')
            ->oldest('id')
            ->get();
    }
}
