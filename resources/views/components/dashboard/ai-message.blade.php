@props(['entry'])

<article data-ai-message data-role="{{ $entry['role'] }}" class="group flex flex-col items-start gap-2 data-[role=user]:items-end">
    <div class="max-w-full rounded-2xl border border-line-soft bg-white px-4 py-3 text-sm leading-7 text-ink group-data-[role=user]:border-brand-100 group-data-[role=user]:bg-brand-50 sm:max-w-[90%]">
        <p data-message-author class="mb-1 text-xs font-semibold text-brand-700">{{ $entry['role'] === 'user' ? 'Anda' : 'Nadi' }}</p>
        <p data-message-text class="whitespace-pre-wrap wrap-anywhere">{{ $entry['message'] }}</p>
        <div data-message-sources class="flex flex-wrap gap-x-4">
            @forelse (($entry['sources'] ?? ($entry['source'] ? [$entry['source']] : [])) as $source)
                <a data-message-source href="{{ $source['url'] }}" class="mt-3 inline-flex text-xs font-semibold text-brand-700 underline underline-offset-4">{{ $source['label'] }}</a>
            @empty
                <a data-message-source hidden class="mt-3 inline-flex text-xs font-semibold text-brand-700 underline underline-offset-4"></a>
            @endforelse
        </div>
    </div>
    <time data-message-time class="px-1 text-[11px] text-ink-faint">{{ $entry['time'] }} WIB</time>
</article>
