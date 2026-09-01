<?php

namespace App\Http\Requests\Owner;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\Task;
use App\TaskPriority;
use App\TaskType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $business = $this->attributes->get('activeBusiness');

        if (! $business instanceof Business) {
            return false;
        }

        Gate::forUser($this->user())->authorize('createForBusiness', [Task::class, $business]);

        return true;
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
        $isDaily = $this->string('type')->toString() === TaskType::Daily->value;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::enum(TaskType::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'due_at' => [Rule::requiredIf(! $isDaily), Rule::prohibitedIf($isDaily), 'nullable', 'date', 'after_or_equal:starts_on'],
            'daily_due_time' => [Rule::requiredIf($isDaily), Rule::prohibitedIf(! $isDaily), 'nullable', 'date_format:H:i'],
            'is_active' => ['required', 'boolean'],
            'assignee_ids' => ['required', 'array', 'min:1'],
            'assignee_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('business_memberships', 'user_id')->where(
                    fn ($query) => $query
                        ->where('business_id', $business->id)
                        ->where('role', BusinessMembership::ROLE_EMPLOYEE)
                        ->where('status', BusinessMembership::STATUS_ACTIVE),
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'assignee_ids.required' => 'Pilih minimal satu karyawan.',
            'assignee_ids.min' => 'Pilih minimal satu karyawan.',
            'assignee_ids.*.exists' => 'Karyawan yang dipilih tidak aktif pada usaha ini.',
            'due_at.required' => 'Tenggat tugas satu kali wajib diisi.',
            'daily_due_time.required' => 'Jam tenggat harian wajib diisi.',
            'ends_on.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }
}
