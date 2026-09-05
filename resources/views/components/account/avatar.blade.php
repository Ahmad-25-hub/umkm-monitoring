@props(['user'])

@php
    $initials = \Illuminate\Support\Str::of($user->name)
        ->squish()
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('');
    $avatarUrl = $user->avatar_path
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path)
        : null;
@endphp

@if ($avatarUrl)
    <img
        src="{{ $avatarUrl }}"
        alt="Foto profil {{ $user->name }}"
        {{ $attributes->class('shrink-0 rounded-full object-cover ring-1 ring-line') }}
    >
@else
    <span
        aria-hidden="true"
        {{ $attributes->class('inline-grid shrink-0 place-items-center rounded-full bg-brand-100 font-semibold text-brand-900 ring-1 ring-brand-200') }}
    >
        {{ $initials ?: 'NA' }}
    </span>
@endif
