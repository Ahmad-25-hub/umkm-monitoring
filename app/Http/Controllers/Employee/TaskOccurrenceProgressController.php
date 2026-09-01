<?php

namespace App\Http\Controllers\Employee;

use App\Actions\UpdateTaskOccurrenceProgressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\UpdateTaskOccurrenceRequest;
use App\Models\TaskOccurrence;
use Illuminate\Http\RedirectResponse;

class TaskOccurrenceProgressController extends Controller
{
    public function update(
        UpdateTaskOccurrenceRequest $request,
        TaskOccurrence $taskOccurrence,
        UpdateTaskOccurrenceProgressAction $updateProgress,
    ): RedirectResponse {
        $updateProgress->execute($taskOccurrence, $request->validated());

        return redirect()->route('employee.dashboard')->with('success', 'Progres tugas berhasil diperbarui.');
    }
}
