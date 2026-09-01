<?php

namespace App\Http\Requests\Employee;

use App\Models\TaskOccurrence;
use App\TaskOccurrenceStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTaskOccurrenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $occurrence = $this->route('taskOccurrence');

        if (! $occurrence instanceof TaskOccurrence) {
            return false;
        }

        Gate::forUser($this->user())->authorize('updateProgress', $occurrence);

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(TaskOccurrenceStatus::class)->only([
                    TaskOccurrenceStatus::InProgress,
                    TaskOccurrenceStatus::Completed,
                ]),
            ],
            'employee_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('status')) {
                    return;
                }

                /** @var TaskOccurrence $occurrence */
                $occurrence = $this->route('taskOccurrence');
                $target = TaskOccurrenceStatus::tryFrom($this->string('status')->toString());
                $allowedTargets = match ($occurrence->status) {
                    TaskOccurrenceStatus::Pending => [TaskOccurrenceStatus::InProgress, TaskOccurrenceStatus::Completed],
                    TaskOccurrenceStatus::InProgress => [TaskOccurrenceStatus::Completed],
                    default => [],
                };

                if (! in_array($target, $allowedTargets, true)) {
                    $validator->errors()->add('status', 'Perubahan status tugas tidak diizinkan.');
                }
            },
        ];
    }
}
