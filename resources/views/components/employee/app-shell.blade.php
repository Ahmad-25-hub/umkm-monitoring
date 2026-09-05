@props([
    'employee',
    'business',
    'title' => 'Dashboard Karyawan — NADI',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Ruang kerja karyawan NADI untuk {{ $business->name }}.">
        <meta name="theme-color" content="#f5f7f4">

        <title>{{ $title }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-canvas font-sans text-ink antialiased">
        <div class="dashboard-frame">
            <x-employee.sidebar :business="$business" />

            <div class="dashboard-content">
                <x-employee.header :employee="$employee" :business="$business" />

                <main id="main-content" class="main-content">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <button
            type="button"
            class="sidebar-backdrop"
            data-sidebar-close
            aria-label="Tutup navigasi"
            tabindex="-1"
        ></button>
    </body>
</html>
