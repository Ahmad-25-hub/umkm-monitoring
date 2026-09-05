@props([
    'owner' => ['name' => 'Owner', 'initials' => 'OW'],
    'businesses' => [],
    'activeBusinessId' => null,
    'title' => 'NADI — Owner Overview',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="NADI gives business owners a clear view of what is happening and what to do next.">
        <meta name="theme-color" content="#f5f7f4">

        <title>{{ $title }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-canvas font-sans text-ink antialiased">
        <div class="dashboard-frame">
            <x-dashboard.sidebar />

            <div class="dashboard-content">
                <x-dashboard.header
                    :owner="$owner"
                    :businesses="$businesses"
                    :active-business-id="$activeBusinessId"
                />

                <main id="main-content" class="main-content">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <button
            type="button"
            class="sidebar-backdrop"
            data-sidebar-close
            aria-label="Close navigation"
            tabindex="-1"
        ></button>
    </body>
</html>
