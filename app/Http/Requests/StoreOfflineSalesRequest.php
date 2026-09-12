<?php

namespace App\Http\Requests;

use App\Models\Business;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfflineSalesRequest extends FormRequest
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
            'order_id' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/'],
            'ordered_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:'.today('Asia/Jakarta')->toDateString()],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*' => ['required', 'array:product_name,quantity,unit_price'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_price' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'save_again' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'business_id.in' => 'Usaha aktif berubah. Buka kembali formulir sebelum menyimpan.',
            'order_id.regex' => 'Nomor transaksi hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'ordered_on.*' => 'Tanggal penjualan harus valid, mulai 01/01/2000 sampai hari ini (WIB).',
            'items.*.product_name.required' => 'Isi nama produk untuk setiap baris.',
            'items.*.product_name.max' => 'Nama produk maksimal 255 karakter.',
            'items.*.quantity.*' => 'Jumlah produk harus berupa bilangan bulat antara 1 dan 100.000.',
            'items.*.unit_price.*' => 'Harga satuan harus berupa rupiah bulat antara 0 dan 1.000.000.000.',
            'items.max' => 'Maksimal 100 produk dalam satu transaksi.',
        ];
    }
}
