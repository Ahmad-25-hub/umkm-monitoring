<?php

namespace App\Http\Requests\Owner;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\TaskOccurrenceStatus;
use App\TaskPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MonitorTaskOccurrencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $business = $this->attributes->get('activeBusiness');

        return $business instanceof Business
            && $business->memberships()
                ->whereBelongsTo($this->user())
                ->where('role', BusinessMembership::ROLE_OWNER)
                ->active()
                ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Business $business */
        $business = $this->attributes->get('activeBusiness');

        return [
            'date' => ['nullable', 'date'],
            'employee_id' => [
                'nullable',
                'integer',
                Rule::exists('business_memberships', 'user_id')->where(
                    fn ($query) => $query
                        ->where('business_id', $business->id)
                        ->where('role', BusinessMembership::ROLE_EMPLOYEE),
                ),
            ],
            'status' => ['nullable', Rule::in([
                ...array_map(fn (TaskOccurrenceStatus $status): string => $status->value, TaskOccurrenceStatus::cases()),
                'overdue',
            ])],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
        ];
    }
}
