@props(['icon' => 'database-zap', 'title', 'description', 'action' => null, 'href' => null, 'compact' => false])

<div @class(['empty-state', 'is-compact' => $compact])>
    <span class="empty-state-icon"><i data-lucide="{{ $icon }}" aria-hidden="true"></i></span>
    <div>
        <h3>{{ $title }}</h3>
        <p>{{ $description }}</p>
        @if ($action && $href)
            <a href="{{ $href }}" class="empty-state-action">{{ $action }}<i data-lucide="arrow-right" aria-hidden="true"></i></a>
        @endif
    </div>
</div>
