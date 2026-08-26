@props([
    'owner',
    'businesses' => [],
    'activeBusinessId' => null,
])

@php
    $activeBusiness = collect($businesses)->firstWhere('id', $activeBusinessId) ?? collect($businesses)->first();
@endphp

<header class="topbar">
    <div class="topbar-inner">
        <button type="button" class="icon-button lg:hidden" data-sidebar-open aria-label="Open navigation" aria-controls="dashboard-sidebar">
            <i data-lucide="menu" aria-hidden="true"></i>
        </button>

        <div class="hidden min-w-0 sm:block">
            <p class="truncate text-[0.95rem] font-semibold tracking-[-0.01em] text-ink">Good morning, {{ $owner['name'] }} <span aria-hidden="true">👋</span></p>
            <p class="mt-0.5 truncate text-xs text-ink-muted">Here’s what’s happening with your business today.</p>
        </div>

        <div class="flex min-w-0 flex-1 items-center justify-end gap-2 sm:gap-3">
            <label class="search-control" aria-label="Search NADI">
                <i data-lucide="search" aria-hidden="true"></i>
                <input type="search" placeholder="Search anything..." data-global-search />
                <kbd>⌘ K</kbd>
            </label>

            <button type="button" class="icon-button relative" aria-label="Notifications">
                <i data-lucide="bell" aria-hidden="true"></i>
                <span class="notification-dot" aria-hidden="true"></span>
            </button>

            <details class="business-selector">
                <summary>
                    <span class="business-avatar">{{ strtoupper(substr($activeBusiness['name'] ?? 'NADI', 0, 1)) }}</span>
                    <span class="hidden min-w-0 text-left md:block">
                        <span class="block text-[0.66rem] font-medium uppercase tracking-[0.08em] text-ink-faint">Business</span>
                        <span class="block max-w-32 truncate text-xs font-semibold text-ink">{{ $activeBusiness['name'] ?? 'Select business' }}</span>
                    </span>
                    <i data-lucide="chevrons-up-down" aria-hidden="true"></i>
                </summary>

                <div class="business-menu">
                    <p class="px-3 pb-2 pt-1 text-[0.66rem] font-semibold uppercase tracking-[0.1em] text-ink-faint">Your businesses</p>
                    @foreach ($businesses as $business)
                        <button type="button" @class(['business-option', 'is-active' => $business['id'] === $activeBusinessId])>
                            <span class="business-option-mark">{{ strtoupper(substr($business['name'], 0, 1)) }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium">{{ $business['name'] }}</span>
                                <span class="block truncate text-[0.68rem] text-ink-faint">{{ $business['location'] }}</span>
                            </span>
                            @if ($business['id'] === $activeBusinessId)
                                <i data-lucide="check" aria-hidden="true"></i>
                            @endif
                        </button>
                    @endforeach
                    <button type="button" class="business-option border-t border-line-soft text-brand-700">
                        <span class="business-option-mark bg-brand-50 text-brand-700"><i data-lucide="plus" aria-hidden="true"></i></span>
                        <span class="font-medium">Add business</span>
                    </button>
                </div>
            </details>

            <button type="button" class="profile-avatar" aria-label="Open profile for {{ $owner['name'] }}">
                {{ $owner['initials'] }}
            </button>
        </div>
    </div>
</header>
