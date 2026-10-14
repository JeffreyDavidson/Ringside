@props(['entry'])
@if ($entry->event)
    <div class="text-ringside-muted mt-2 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
        <span
            >{{ $entry->event->name }} · {{ $entry->event->local_date?->toDateString() ?? __('storylines.undated_source') }}
            @if ($entry->event->trashed()) ·{{ __('storylines.removed') }}@endif
        </span>
        @if (! $entry->event->trashed())
            <a
                class="min-h-11 py-3 underline underline-offset-4"
                href="{{ route('events.show', $entry->event) }}"
            >{{ __('storylines.card') }}</a>
            <a
                class="min-h-11 py-3 underline underline-offset-4"
                href="{{ route('events.pack.show', $entry->event) }}"
            >{{ __('storylines.pack') }}</a>
            <a
                class="min-h-11 py-3 underline underline-offset-4"
                href="{{ route('events.live-show.show', $entry->event) }}"
            >{{ __('storylines.live') }}</a>
        @endif
    </div>
@endif
