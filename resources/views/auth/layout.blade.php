@php
    $authHomeUrl = trim($__env->yieldContent('homeUrl')) ?: route('home');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="@yield('metaDescription', 'Masuk atau daftarkan usaha Anda di NADI.')">
        <meta name="theme-color" content="#f5f7f4">

        <title>@yield('title') — NADI</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-canvas font-sans text-ink antialiased">
        <main class="grid min-h-screen lg:grid-cols-[minmax(0,0.9fr)_minmax(32rem,1.1fr)]">
            <section class="relative hidden overflow-hidden bg-[#17201d] p-12 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_25%_12%,rgba(72,156,119,0.28),transparent_34%)]" aria-hidden="true"></div>

                <a href="{{ $authHomeUrl }}" class="relative inline-flex w-fit items-center gap-3 rounded-xl">
                    <span class="brand-symbol" aria-hidden="true"><span></span><span></span><span></span></span>
                    <span>
                        <span class="block text-lg font-semibold tracking-[0.18em]">NADI</span>
                        <span class="mt-1 block text-xs text-white/50">Business Intelligence</span>
                    </span>
                </a>

                <div class="relative max-w-xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#8ed3b6]">@yield('contextKicker', 'Ruang kerja pemilik usaha')</p>
                    <h1 class="mt-5 text-4xl font-medium leading-tight tracking-[-0.04em] xl:text-5xl">@yield('contextTitle', 'Satu tempat untuk memahami usaha dan mengambil keputusan berikutnya.')</h1>
                    <p class="mt-5 max-w-lg text-sm leading-7 text-white/55">@yield('contextDescription', 'Data pengguna dipisahkan dari data usaha, sehingga akun Anda siap mengelola lebih dari satu usaha di masa depan.')</p>
                </div>

                <p class="relative text-xs text-white/35">@yield('contextFooter', 'Akses aman untuk pemilik usaha NADI.')</p>
            </section>

            <section class="flex items-center justify-center px-5 py-10 sm:px-8 lg:px-12">
                <div class="w-full max-w-md">
                    <a href="{{ $authHomeUrl }}" class="mb-10 inline-flex items-center gap-3 rounded-xl lg:hidden">
                        <span class="brand-symbol !border-brand-100 !bg-brand-50" aria-hidden="true"><span></span><span></span><span></span></span>
                        <span class="text-base font-semibold tracking-[0.18em] text-brand-900">NADI</span>
                    </a>

                    @if (session('status'))
                        <div role="status" class="mb-6 rounded-xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm leading-6 text-brand-900">
                            {{ session('status') }}
                        </div>
                    @endif

                    @yield('content')
                </div>
            </section>
        </main>
    </body>
</html>
