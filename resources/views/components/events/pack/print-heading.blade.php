@props(['event', 'content', 'pack', 'section'])
<div class="pack-print-heading">
    <img src="{{ asset('images/ringside-wordmark-light.svg') }}" alt="Ringside" width="130" height="30" />
    <div>
        <strong>{{ $event->name }}</strong
        ><span>{{ $section }} · {{ $content['revision'] ?: __('event-pack.no_revision') }}</span>
    </div>
</div>
<p class="pack-print-meta">
    {{ $event->local_date?->format('M j, Y H:i e') ?? __('event-pack.unscheduled') }} · {{ $event->venue?->name ?? __('event-pack.no_venue') }}<br />
    {{ __('event-pack.timezone') }}: {{ $content['timezone'] }} · {{ __('event-pack.doors') }}: {{ str_replace('T', ' ', $content['doors_at'] ?? '—') }} · {{ __('event-pack.master') }}: {{ $content['master'] ?: '—' }}<br />
    {{ __('event-pack.generated') }}: {{ now()->timezone($content['timezone'])->format('Y-m-d H:i') }} · {{ __('event-pack.saved_at') }}: {{ $pack?->updated_at->timezone($content['timezone'])->format('Y-m-d H:i') ?? __('event-pack.not_saved') }}
</p>
