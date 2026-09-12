<?php

namespace App\Http\Requests;

use App\Models\Business;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewOfflineSalesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->attributes->get('activeBusiness') ?? $this->attributes->get('activeEmployeeBusiness')) instanceof Business;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $business = $this->attributes->get('activeBusiness') ?? $this->attributes->get('activeEmployeeBusiness');

        return [
            'business_id' => ['required', 'integer', Rule::in([$business?->id])],
            'sales_file' => ['required', 'file', 'mimes:xlsx', 'extensions:xlsx', 'max:10240'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'business_id.in' => 'Usaha aktif berubah. Buka kembali formulir sebelum mengunggah.',
            'sales_file.required' => 'Pilih template penjualan offline yang sudah diisi.',
            'sales_file.mimes' => 'Gunakan template Excel NADI dalam format XLSX.',
            'sales_file.extensions' => 'Gunakan template Excel NADI dalam format XLSX.',
            'sales_file.max' => 'Ukuran file maksimal 10 MB.',
        ];
    }
}
