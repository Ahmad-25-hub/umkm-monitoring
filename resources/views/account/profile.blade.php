<x-account.app-shell :navigation="$navigation" title="Profil Saya — NADI">
    <div class="mx-auto grid max-w-5xl gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-brand-700">Akun pribadi</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-[-0.03em] text-ink sm:text-3xl">Profil Saya</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-muted">Kelola identitas yang tampil di ruang kerja NADI.</p>
            </div>
            <nav class="flex rounded-xl border border-line bg-white p-1" aria-label="Navigasi akun">
                <a href="{{ route('profile.show') }}" class="rounded-lg bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700" aria-current="page">Profil Saya</a>
                <a href="{{ route('account.settings') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-ink-muted transition hover:bg-canvas hover:text-ink">Pengaturan Akun</a>
            </nav>
        </div>

        <x-account.status-message />

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <section class="panel p-5 sm:p-7" aria-labelledby="profile-information-heading">
                <div class="border-b border-line-soft pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-faint">Informasi Profil</p>
                    <h2 id="profile-information-heading" class="mt-2 text-lg font-semibold text-ink">Identitas utama</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-muted">Email ditampilkan sebagai referensi dan hanya dapat diubah melalui Pengaturan Akun.</p>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="mt-6 grid gap-5" data-submit-once>
                    @csrf
                    @method('PATCH')

                    <div class="grid gap-2">
                        <label for="profile-name" class="text-sm font-semibold text-ink">Nama lengkap</label>
                        <input
                            id="profile-name"
                            name="name"
                            type="text"
                            value="{{ old('name', $user->name) }}"
                            autocomplete="name"
                            required
                            @class([
                                'h-12 rounded-xl border bg-white px-4 text-sm text-ink shadow-sm transition placeholder:text-ink-faint focus:border-brand-500 focus:ring-4 focus:ring-brand-100',
                                'border-critical' => $errors->profileUpdate->has('name'),
                                'border-line' => ! $errors->profileUpdate->has('name'),
                            ])
                        >
                        @error('name', 'profileUpdate')
                            <p class="text-xs font-medium text-critical">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label for="profile-email" class="text-sm font-semibold text-ink">Email</label>
                        <input id="profile-email" type="email" value="{{ $user->email }}" class="h-12 rounded-xl border border-line bg-canvas px-4 text-sm text-ink-muted" readonly aria-readonly="true">
                        <p class="text-xs leading-5 text-ink-faint">Perubahan email memerlukan password dan verifikasi alamat baru.</p>
                    </div>

                    <div>
                        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-brand-700 px-5 text-sm font-semibold text-white transition hover:bg-brand-900 disabled:cursor-wait disabled:opacity-60">
                            <i data-lucide="save" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Simpan profil</span>
                        </button>
                    </div>
                </form>
            </section>

            <section class="panel p-5 sm:p-7" aria-labelledby="profile-photo-heading">
                <div class="border-b border-line-soft pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-faint">Foto Profil</p>
                    <h2 id="profile-photo-heading" class="mt-2 text-lg font-semibold text-ink">Avatar Anda</h2>
                </div>

                <div class="mt-6 flex items-center gap-4">
                    <x-account.avatar :user="$user" class="h-20 w-20 text-xl" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-ink">{{ $user->name }}</p>
                        <p class="mt-1 text-xs leading-5 text-ink-muted">JPG, PNG, atau WebP. Maksimal 2 MB.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="mt-6 grid gap-3" data-submit-once>
                    @csrf
                    @method('PATCH')
                    <label for="avatar" class="text-sm font-semibold text-ink">Pilih foto baru</label>
                    <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required class="block w-full rounded-xl border border-line bg-white p-2 text-xs text-ink-muted file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                    @error('avatar', 'avatarUpdate')
                        <p class="text-xs font-medium text-critical">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-line bg-white px-4 text-sm font-semibold text-ink transition hover:border-brand-200 hover:bg-brand-50 disabled:cursor-wait disabled:opacity-60">
                        <i data-lucide="upload" class="h-4 w-4 text-brand-700" aria-hidden="true"></i>
                        <span>Unggah foto</span>
                    </button>
                </form>

                @if ($user->avatar_path)
                    <form method="POST" action="{{ route('profile.avatar.destroy') }}" class="mt-3" data-confirm="Hapus foto profil dan kembali menggunakan avatar inisial?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold text-critical transition hover:bg-red-50">
                            <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Hapus foto</span>
                        </button>
                    </form>
                @endif
            </section>
        </div>
    </div>
</x-account.app-shell>
