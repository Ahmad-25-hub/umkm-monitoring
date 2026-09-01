<?php

namespace App\Http\Requests\Employee;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class JoinBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_code' => ['required', 'string', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'business_code.required' => 'Kode Usaha wajib diisi.',
            'business_code.max' => 'Kode Usaha tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_code' => Str::upper($this->string('business_code')->trim()->toString()),
        ]);
    }
}
