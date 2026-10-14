@props(['sheet', 'section'])
<div class="pack-print-heading">
    <img src="{{ asset('images/ringside-wordmark-light.svg') }}" alt="Ringside" width="130" height="30" />
    <div>
        <strong>{{ $sheet['eventName'] }}</strong
        ><span>{{ $section }} · {{ $sheet['revision'] ?: __('event-pack.no_revision') }}</span>
    </div>
</div>
<p class="pack-print-meta">
    {{ $sheet['eventDate'] ?? __('event-pack.unscheduled') }} · {{ $sheet['venue'] ?? __('event-pack.no_venue') }}<br />{{ __('production.source.'.$sheet['source']) }} · {{ $sheet['version'] > 0 ? __('production.version', ['version' => $sheet['version']]) : __('event-pack.not_saved') }} · {{ $sheet['timezone'] }}<br />{{ __('event-pack.generated') }}: {{ now()->setTimezone($sheet['timezone'])->format('Y-m-d H:i') }}
</p>
@if ($sheet['needsReview'])
    <p class="pack-print-warning">{{ __('production.source_review') }}</p>
@endif
