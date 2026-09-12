<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPasswordResetOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return ['otp' => ['required', 'string', 'regex:/\A[0-9]{6}\z/']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.string' => 'Kode OTP harus terdiri dari 6 angka.',
            'otp.regex' => 'Kode OTP harus terdiri dari 6 angka.',
        ];
    }
}
