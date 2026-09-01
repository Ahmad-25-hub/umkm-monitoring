<?php

namespace App\Actions;

use App\Models\TaskOccurrence;
use App\TaskOccurrenceStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateTaskOccurrenceProgressAction
{
    /**
     * @param  array{status: string, employee_note?: ?string}  $data
     */
    public function execute(TaskOccurrence $occurrence, array $data): TaskOccurrence
    {
        return DB::transaction(function () use ($occurrence, $data): TaskOccurrence {
            /** @var TaskOccurrence $lockedOccurrence */
            $lockedOccurrence = TaskOccurrence::query()->lockForUpdate()->findOrFail($occurrence->id);
            $target = TaskOccurrenceStatus::from($data['status']);
            $allowedTargets = match ($lockedOccurrence->status) {
                TaskOccurrenceStatus::Pending => [TaskOccurrenceStatus::InProgress, TaskOccurrenceStatus::Completed],
                TaskOccurrenceStatus::InProgress => [TaskOccurrenceStatus::Completed],
                default => [],
            };

            if (! in_array($target, $allowedTargets, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Perubahan status tugas tidak diizinkan.',
                ]);
            }

            if ($lockedOccurrence->started_at === null) {
                $lockedOccurrence->started_at = now();
            }

            if ($target === TaskOccurrenceStatus::Completed && $lockedOccurrence->completed_at === null) {
                $lockedOccurrence->completed_at = now();
            }

            $lockedOccurrence->status = $target;

            if (array_key_exists('employee_note', $data)) {
                $lockedOccurrence->employee_note = $data['employee_note'];
            }

            $lockedOccurrence->save();

            return $lockedOccurrence;
        });
    }
}
