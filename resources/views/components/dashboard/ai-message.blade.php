@props(['entry'])

<article data-ai-message data-role="{{ $entry['role'] }}" class="group flex flex-col items-start gap-2 data-[role=user]:items-end">
    <div class="max-w-full rounded-2xl border border-line-soft bg-white px-4 py-3 text-sm leading-7 text-ink group-data-[role=user]:border-brand-100 group-data-[role=user]:bg-brand-50 sm:max-w-[90%]">
        <p data-message-author class="mb-1 text-xs font-semibold text-brand-700">{{ $entry['role'] === 'user' ? 'Anda' : 'Nadi' }}</p>
        <p data-message-text class="whitespace-pre-wrap wrap-anywhere">{{ $entry['message'] }}</p>
        <a data-message-source @if ($entry['source']) href="{{ $entry['source']['url'] }}" @else hidden @endif class="mt-3 inline-flex text-xs font-semibold text-brand-700 underline underline-offset-4">{{ $entry['source']['label'] ?? '' }}</a>
    </div>
    <time data-message-time class="px-1 text-[11px] text-ink-faint">{{ $entry['time'] }} WIB</time>
</article>
