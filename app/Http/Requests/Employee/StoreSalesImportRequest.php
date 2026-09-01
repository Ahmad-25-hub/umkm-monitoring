<?php

namespace App\Http\Requests\Employee;

use App\Models\Business;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSalesImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->attributes->get('activeEmployeeBusiness') instanceof Business;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sales_file' => ['required', 'file', 'mimes:csv,txt', 'extensions:csv', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sales_file.required' => 'Pilih file penjualan TikTok Seller yang akan diunggah.',
            'sales_file.file' => 'File penjualan tidak dapat dibaca.',
            'sales_file.mimes' => 'File penjualan harus berformat CSV.',
            'sales_file.extensions' => 'File penjualan harus berformat CSV.',
            'sales_file.max' => 'Ukuran file penjualan maksimal 10 MB.',
        ];
    }
}
