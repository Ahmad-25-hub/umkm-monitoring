@php
    $primaryNavigation = [
        ['label' => 'Overview', 'icon' => 'layout-dashboard', 'active' => true],
        ['label' => 'Sales', 'icon' => 'chart-no-axes-combined'],
        ['label' => 'Employees', 'icon' => 'users-round'],
        ['label' => 'Inventory', 'icon' => 'package'],
        ['label' => 'Customers', 'icon' => 'contact-round'],
        ['label' => 'Reports', 'icon' => 'file-chart-column'],
        ['label' => 'AI Insights', 'icon' => 'sparkles'],
    ];

    $secondaryNavigation = [
        ['label' => 'Settings', 'icon' => 'settings-2'],
        ['label' => 'Help & Support', 'icon' => 'circle-help'],
    ];
@endphp

<aside id="dashboard-sidebar" class="sidebar" aria-label="Primary navigation">
    <div class="flex h-full flex-col">
        <div class="brand-lockup">
            <a href="{{ route('overview') }}" class="brand-mark" aria-label="NADI overview">
                <span class="brand-symbol" aria-hidden="true">
                    <span></span><span></span><span></span>
                </span>
                <span>
                    <span class="block text-[1.1rem] font-semibold tracking-[0.18em] text-white">NADI</span>
                    <span class="mt-0.5 block text-[0.65rem] font-medium tracking-[0.08em] text-white/45">Business Intelligence</span>
                </span>
            </a>

            <button type="button" class="sidebar-close" data-sidebar-close aria-label="Close navigation">
                <i data-lucide="x" aria-hidden="true"></i>
            </button>

            <button type="button" class="sidebar-collapse" data-sidebar-collapse aria-label="Collapse navigation" aria-expanded="true">
                <i data-lucide="chevrons-left" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="flex min-h-0 flex-1 flex-col px-4 pb-5 pt-7">
            <p class="nav-label">Workspace</p>
            <ul class="mt-3 space-y-1">
                @foreach ($primaryNavigation as $item)
                    <li>
                        <a
                            href="{{ $item['active'] ?? false ? route('overview') : '#' }}"
                            title="{{ $item['label'] }}"
                            @class(['nav-item', 'is-active' => $item['active'] ?? false])
                            @if (! ($item['active'] ?? false)) aria-disabled="true" @endif
                        >
                            <i data-lucide="{{ $item['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $item['label'] }}</span>
                            @if ($item['label'] === 'AI Insights')
                                <span class="nav-badge">Soon</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-auto pt-8">
                <div class="sidebar-context">
                    <span class="sidebar-context-icon"><i data-lucide="radio-tower" aria-hidden="true"></i></span>
                    <div>
                        <p class="text-xs font-medium text-white/90">Data connections</p>
                        <p class="mt-1 text-[0.68rem] leading-4 text-white/42">Connect your tools when you are ready.</p>
                    </div>
                </div>

                <ul class="mt-5 space-y-1 border-t border-white/[0.07] pt-4">
                    @foreach ($secondaryNavigation as $item)
                        <li>
                            <a href="#" class="nav-item" aria-disabled="true" title="{{ $item['label'] }}">
                                <i data-lucide="{{ $item['icon'] }}" aria-hidden="true"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <form method="POST" action="{{ route('logout') }}" class="mt-1">
                    @csrf
                    <button type="submit" class="nav-item w-full" title="Keluar">
                        <i data-lucide="log-out" aria-hidden="true"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </nav>
    </div>
</aside>
