<x-account.app-shell :navigation="$navigation" title="Pengaturan Akun — NADI">
    <div class="mx-auto grid max-w-5xl gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-brand-700">Akun pribadi</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-[-0.03em] text-ink sm:text-3xl">Pengaturan Akun</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-muted">Perbarui email, password, dan keamanan sesi Anda.</p>
            </div>
            <nav class="flex rounded-xl border border-line bg-white p-1" aria-label="Navigasi akun">
                <a href="{{ route('profile.show') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-ink-muted transition hover:bg-canvas hover:text-ink">Profil Saya</a>
                <a href="{{ route('account.settings') }}" class="rounded-lg bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700" aria-current="page">Pengaturan Akun</a>
            </nav>
        </div>

        <x-account.status-message />

        <section class="panel p-5 sm:p-7" aria-labelledby="email-settings-heading">
            <div class="border-b border-line-soft pb-5">
                <div class="flex items-start gap-3">
                    <span class="inline-grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700"><i data-lucide="mail" class="h-4 w-4" aria-hidden="true"></i></span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-faint">Alamat Email</p>
                        <h2 id="email-settings-heading" class="mt-1.5 text-lg font-semibold text-ink">Ubah email dengan verifikasi</h2>
                        <p class="mt-2 text-sm leading-6 text-ink-muted">Email lama tetap aktif sampai tautan pada email baru dikonfirmasi.</p>
                    </div>
                </div>
            </div>

            @if ($user->pending_email)
                <div class="mt-5 flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-amber-900">Menunggu verifikasi</p>
                        <p class="mt-1 break-all text-xs leading-5 text-amber-800">{{ $user->pending_email }}</p>
                    </div>
                    <form method="POST" action="{{ route('account.email.verification.send') }}" data-submit-once>
                        @csrf
                        <button type="submit" class="rounded-xl border border-amber-300 bg-white px-4 py-2.5 text-xs font-semibold text-amber-900 transition hover:bg-amber-100 disabled:cursor-wait disabled:opacity-60">Kirim ulang verifikasi</button>
                    </form>
                </div>
            @endif

            <form method="POST" action="{{ route('account.email.update') }}" class="mt-6 grid gap-5 sm:grid-cols-2" data-submit-once>
                @csrf
                @method('PATCH')

                <div class="grid gap-2">
                    <label for="current-email" class="text-sm font-semibold text-ink">Email saat ini</label>
                    <input id="current-email" type="email" value="{{ $user->email }}" class="h-12 rounded-xl border border-line bg-canvas px-4 text-sm text-ink-muted" readonly aria-readonly="true">
                </div>

                <div class="grid gap-2">
                    <label for="new-email" class="text-sm font-semibold text-ink">Email baru</label>
                    <input id="new-email" name="new_email" type="email" value="{{ old('new_email', $user->pending_email) }}" autocomplete="email" required @class(['h-12 rounded-xl border bg-white px-4 text-sm text-ink shadow-sm transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100', 'border-critical' => $errors->emailUpdate->has('new_email'), 'border-line' => ! $errors->emailUpdate->has('new_email')])>
                    @error('new_email', 'emailUpdate')
                        <p class="text-xs font-medium text-critical">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <label for="email-current-password" class="text-sm font-semibold text-ink">Password saat ini</label>
                    <div class="relative">
                        <input id="email-current-password" name="current_password" type="password" autocomplete="current-password" required @class(['h-12 w-full rounded-xl border bg-white px-4 pr-12 text-sm text-ink shadow-sm transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100', 'border-critical' => $errors->emailUpdate->has('current_password'), 'border-line' => ! $errors->emailUpdate->has('current_password')])>
                        <button type="button" class="absolute inset-y-1 right-1 inline-grid w-10 place-items-center rounded-lg text-ink-faint transition hover:bg-canvas hover:text-ink" data-password-toggle aria-controls="email-current-password" aria-label="Tampilkan password">
                            <i data-lucide="eye" class="h-4 w-4" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('current_password', 'emailUpdate')
                        <p class="text-xs font-medium text-critical">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-brand-700 px-5 text-sm font-semibold text-white transition hover:bg-brand-900 disabled:cursor-wait disabled:opacity-60">
                        <i data-lucide="send" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Kirim tautan verifikasi</span>
                    </button>
                </div>
            </form>
        </section>

        <section class="panel p-5 sm:p-7" aria-labelledby="password-settings-heading">
            <div class="border-b border-line-soft pb-5">
                <div class="flex items-start gap-3">
                    <span class="inline-grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700"><i data-lucide="key-round" class="h-4 w-4" aria-hidden="true"></i></span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-faint">Password</p>
                        <h2 id="password-settings-heading" class="mt-1.5 text-lg font-semibold text-ink">Ubah password</h2>
                        <p class="mt-2 text-sm leading-6 text-ink-muted">Gunakan password unik yang tidak dipakai pada layanan lain.</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('account.password.update') }}" class="mt-6 grid gap-5" data-submit-once>
                @csrf
                @method('PATCH')

                @foreach ([
                    ['id' => 'password-current', 'name' => 'current_password', 'label' => 'Password saat ini', 'autocomplete' => 'current-password'],
                    ['id' => 'password-new', 'name' => 'password', 'label' => 'Password baru', 'autocomplete' => 'new-password'],
                    ['id' => 'password-confirmation', 'name' => 'password_confirmation', 'label' => 'Konfirmasi password baru', 'autocomplete' => 'new-password'],
                ] as $field)
                    <div class="grid gap-2">
                        <label for="{{ $field['id'] }}" class="text-sm font-semibold text-ink">{{ $field['label'] }}</label>
                        <div class="relative">
                            <input id="{{ $field['id'] }}" name="{{ $field['name'] }}" type="password" autocomplete="{{ $field['autocomplete'] }}" required @if ($field['name'] === 'password') data-new-password @endif @class(['h-12 w-full rounded-xl border bg-white px-4 pr-12 text-sm text-ink shadow-sm transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100', 'border-critical' => $errors->passwordUpdate->has($field['name']), 'border-line' => ! $errors->passwordUpdate->has($field['name'])])>
                            <button type="button" class="absolute inset-y-1 right-1 inline-grid w-10 place-items-center rounded-lg text-ink-faint transition hover:bg-canvas hover:text-ink" data-password-toggle aria-controls="{{ $field['id'] }}" aria-label="Tampilkan {{ strtolower($field['label']) }}">
                                <i data-lucide="eye" class="h-4 w-4" aria-hidden="true"></i>
                            </button>
                        </div>
                        @error($field['name'], 'passwordUpdate')
                            <p class="text-xs font-medium text-critical">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <div class="rounded-2xl bg-canvas p-4" aria-live="polite">
                    <p class="text-xs font-semibold text-ink">Password baru harus memiliki:</p>
                    <ul class="mt-3 grid gap-2 text-xs text-ink-muted sm:grid-cols-3">
                        <li class="flex items-center gap-2" data-password-requirement="length"><i data-lucide="circle" class="h-3.5 w-3.5" aria-hidden="true"></i>Minimal 8 karakter</li>
                        <li class="flex items-center gap-2" data-password-requirement="case"><i data-lucide="circle" class="h-3.5 w-3.5" aria-hidden="true"></i>Huruf besar dan kecil</li>
                        <li class="flex items-center gap-2" data-password-requirement="number"><i data-lucide="circle" class="h-3.5 w-3.5" aria-hidden="true"></i>Setidaknya satu angka</li>
                    </ul>
                </div>

                <div>
                    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-brand-700 px-5 text-sm font-semibold text-white transition hover:bg-brand-900 disabled:cursor-wait disabled:opacity-60">
                        <i data-lucide="shield-check" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Perbarui password</span>
                    </button>
                </div>
            </form>
        </section>

        <section class="panel p-5 sm:p-7" aria-labelledby="session-settings-heading">
            <div class="border-b border-line-soft pb-5">
                <div class="flex items-start gap-3">
                    <span class="inline-grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700"><i data-lucide="monitor-smartphone" class="h-4 w-4" aria-hidden="true"></i></span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-faint">Perangkat dan Sesi</p>
                        <h2 id="session-settings-heading" class="mt-1.5 text-lg font-semibold text-ink">Sesi yang aktif</h2>
                        <p class="mt-2 text-sm leading-6 text-ink-muted">Alamat IP disamarkan untuk menjaga privasi.</p>
                    </div>
                </div>
            </div>

            @if ($sessionsSupported)
                <div class="mt-5 divide-y divide-line-soft rounded-2xl border border-line-soft">
                    @forelse ($sessions as $session)
                        <div class="flex items-start gap-3 p-4">
                            <i data-lucide="monitor" class="mt-0.5 h-4 w-4 shrink-0 text-ink-faint" aria-hidden="true"></i>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-ink">{{ $session['device'] }}</p>
                                    @if ($session['isCurrent'])
                                        <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[0.65rem] font-semibold text-brand-700">Sesi saat ini</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-ink-muted">{{ $session['ipAddress'] }} · Aktif {{ $session['lastActive'] }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="p-4 text-sm text-ink-muted">Belum ada data sesi yang dapat ditampilkan.</p>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('account.sessions.destroy-others') }}" class="mt-5 grid gap-3" data-confirm="Keluar dari semua perangkat lain? Anda akan tetap masuk di perangkat ini." data-submit-once>
                    @csrf
                    @method('DELETE')
                    <label for="sessions-current-password" class="text-sm font-semibold text-ink">Konfirmasi password saat ini</label>
                    <div class="relative max-w-xl">
                        <input id="sessions-current-password" name="current_password" type="password" autocomplete="current-password" required @class(['h-12 w-full rounded-xl border bg-white px-4 pr-12 text-sm text-ink shadow-sm transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100', 'border-critical' => $errors->sessionDestroy->has('current_password'), 'border-line' => ! $errors->sessionDestroy->has('current_password')])>
                        <button type="button" class="absolute inset-y-1 right-1 inline-grid w-10 place-items-center rounded-lg text-ink-faint transition hover:bg-canvas hover:text-ink" data-password-toggle aria-controls="sessions-current-password" aria-label="Tampilkan password">
                            <i data-lucide="eye" class="h-4 w-4" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('current_password', 'sessionDestroy')
                        <p class="text-xs font-medium text-critical">{{ $message }}</p>
                    @enderror
                    <div>
                        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-critical/30 bg-white px-5 text-sm font-semibold text-critical transition hover:bg-red-50 disabled:cursor-wait disabled:opacity-60">
                            <i data-lucide="log-out" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Keluar dari perangkat lain</span>
                        </button>
                    </div>
                </form>
            @else
                <div class="mt-5 rounded-2xl border border-line-soft bg-canvas p-4 text-sm leading-6 text-ink-muted">
                    Daftar perangkat hanya tersedia ketika aplikasi menggunakan penyimpanan sesi database.
                </div>
            @endif
        </section>
    </div>
</x-account.app-shell>
