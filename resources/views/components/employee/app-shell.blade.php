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
    <body class="employee-workspace min-h-screen bg-canvas font-sans text-ink antialiased">
        <a href="#main-content" class="skip-link">Lewati ke konten utama</a>
        <div class="dashboard-frame">
            <x-employee.sidebar :business="$business" />

            <div class="dashboard-content">
                <x-employee.header :employee="$employee" :business="$business" />

                <main id="main-content" tabindex="-1" class="main-content">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <nav class="employee-bottom-nav" aria-label="Akses cepat karyawan">
            <a href="{{ route('employee.dashboard') }}#employee-tasks" data-section-link="employee-tasks"><i data-lucide="activity" aria-hidden="true"></i><span>Tugas</span></a>
            <a href="{{ route('employee.dashboard') }}#sales-upload" data-section-link="sales-upload"><i data-lucide="upload" aria-hidden="true"></i><span>Unggah</span></a>
            <a href="{{ route('employee.dashboard') }}#sales-summary" data-section-link="sales-summary"><i data-lucide="shopping-basket" aria-hidden="true"></i><span>Unit terjual</span></a>
        </nav>

        <button
            type="button"
            class="sidebar-backdrop"
            data-sidebar-close
            aria-label="Tutup navigasi"
            tabindex="-1"
        ></button>
    </body>
</html>
