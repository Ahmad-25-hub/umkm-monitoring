@php
    $messages = [
        'profile-updated' => 'Informasi profil berhasil diperbarui.',
        'avatar-updated' => 'Foto profil berhasil diperbarui.',
        'avatar-deleted' => 'Foto profil dihapus. Avatar inisial kembali digunakan.',
        'email-verification-sent' => 'Tautan verifikasi telah dikirim ke email baru.',
        'email-updated' => 'Alamat email berhasil diverifikasi dan diperbarui.',
        'password-updated' => 'Password berhasil diperbarui. Anda tetap masuk di perangkat ini.',
        'other-sessions-destroyed' => 'Semua sesi lain berhasil dikeluarkan.',
    ];
    $message = $messages[session('status')] ?? null;
@endphp

@if ($message)
    <div class="flex items-start gap-3 rounded-2xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-brand-900" role="status">
        <i data-lucide="circle-check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" aria-hidden="true"></i>
        <p>{{ $message }}</p>
    </div>
@endif
