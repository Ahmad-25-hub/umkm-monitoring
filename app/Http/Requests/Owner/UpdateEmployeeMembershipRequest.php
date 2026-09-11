<?php

namespace App\Http\Requests\Owner;

use App\Models\Business;
use App\Models\BusinessMembership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateEmployeeMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $membership = $this->route('membership');
        $business = $this->attributes->get('activeBusiness');

        abort_unless(
            $membership instanceof BusinessMembership
                && $business instanceof Business
                && $membership->business_id === $business->id
                && $membership->role === BusinessMembership::ROLE_EMPLOYEE,
            404,
        );

        Gate::forUser($this->user())->authorize('manageEmployees', $business);

        return true;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in([
                BusinessMembership::STATUS_ACTIVE,
                BusinessMembership::STATUS_INACTIVE,
            ])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status.required' => 'Pilih status akses karyawan.',
            'status.string' => 'Status akses karyawan tidak valid.',
            'status.in' => 'Status akses karyawan tidak valid.',
        ];
    }
}
