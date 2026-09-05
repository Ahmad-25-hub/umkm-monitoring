@props([
    'navigation',
    'title' => 'Akun Saya — NADI',
])

@if ($navigation['mode'] === 'owner')
    <x-dashboard.app-shell
        :owner="$navigation['person']"
        :businesses="$navigation['businesses']"
        :active-business-id="$navigation['activeBusinessId']"
        :title="$title"
    >
        {{ $slot }}
    </x-dashboard.app-shell>
@elseif ($navigation['mode'] === 'employee')
    <x-employee.app-shell
        :employee="$navigation['person']"
        :business="$navigation['business']"
        :title="$title"
    >
        {{ $slot }}
    </x-employee.app-shell>
@else
    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="theme-color" content="#f5f7f4">
            <title>{{ $title }}</title>
            @fonts
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        </head>
        <body class="min-h-screen bg-canvas font-sans text-ink antialiased">
            <header class="topbar">
                <div class="topbar-inner justify-between">
                    <a href="{{ route('profile.show') }}" class="text-base font-semibold tracking-[0.14em] text-brand-900">NADI</a>
                    <x-account.profile-dropdown :role-label="$navigation['roleLabel']" :logout-route="$navigation['logoutRoute']" />
                </div>
            </header>
            <main id="main-content" class="main-content">
                {{ $slot }}
            </main>
        </body>
    </html>
@endif
