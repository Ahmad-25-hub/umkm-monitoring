<?php

namespace App\Http\Requests\Account;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\Attributes\ErrorBag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

#[ErrorBag('emailUpdate')]
class UpdateEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'new_email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::notIn([$this->user()?->email]),
                Rule::unique('users', 'email')->ignore($this->user()?->getKey()),
                Rule::unique('users', 'pending_email')->ignore($this->user()?->getKey()),
            ],
            'current_password' => ['required', 'current_password:web'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'new_email.required' => 'Email baru wajib diisi.',
            'new_email.email' => 'Masukkan alamat email baru yang valid.',
            'new_email.not_in' => 'Email baru harus berbeda dari email saat ini.',
            'new_email.unique' => 'Alamat email tersebut tidak dapat digunakan.',
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'new_email' => Str::lower($this->string('new_email')->trim()->toString()),
        ]);
    }
}
