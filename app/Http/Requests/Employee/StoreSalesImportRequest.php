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
            'sales_file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'extensions:csv,xlsx', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sales_file.required' => 'Pilih file pesanan TikTok Seller atau Shopee yang akan diunggah.',
            'sales_file.file' => 'File penjualan tidak dapat dibaca.',
            'sales_file.mimes' => 'File pesanan harus berformat CSV TikTok Seller atau XLSX Shopee.',
            'sales_file.extensions' => 'File pesanan harus berformat CSV TikTok Seller atau XLSX Shopee.',
            'sales_file.max' => 'Ukuran file pesanan maksimal 10 MB.',
        ];
    }
}
