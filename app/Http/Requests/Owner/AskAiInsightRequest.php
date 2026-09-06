<?php

namespace App\Http\Requests\Owner;

use App\Models\Business;
use App\Models\BusinessMembership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AskAiInsightRequest extends FormRequest
{
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
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'message' => [$this->isMethod('delete') ? 'prohibited' : 'required', 'string', 'max:1000'],
            'business_id' => ['required', 'integer', Rule::in([$this->attributes->get('activeBusiness')->id])],
            'history' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Tulis pertanyaan Anda terlebih dahulu.',
            'message.string' => 'Pertanyaan harus berupa teks.',
            'message.max' => 'Pertanyaan maksimal 1.000 karakter.',
            'message.prohibited' => 'Pesan tidak diperlukan saat menghapus percakapan.',
            'business_id.required' => 'Muat ulang halaman untuk memilih usaha aktif.',
            'business_id.integer' => 'Usaha aktif tidak valid. Muat ulang halaman.',
            'business_id.in' => 'Usaha aktif telah berubah. Muat ulang halaman sebelum bertanya.',
            'history.prohibited' => 'Riwayat percakapan dikelola oleh Nadi.',
        ];
    }
}
