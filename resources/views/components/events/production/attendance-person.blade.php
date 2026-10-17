@props(['person', 'event', 'complete', 'attendance'])
<article wire:key="person-{{ $person['key'] }}" class="flex flex-wrap items-start justify-between gap-4 py-5">
    <div class="min-w-0">
        <h3 class="text-lg font-semibold break-words">{{ $person['name'] }}</h3>
        <p class="text-muted-foreground mt-1 text-sm">
            {{ $person['role'] }}
            @if (! str_starts_with($person['key'], 'crew:')) ·{{ __('live-show.matches') }} {{ implode(', ', $person['matches']) }} @endif
        </p>
        @foreach ($person['assignments'] ?? [] as $assignment)
            <p class="mt-2 text-sm">
                {{ $assignment['station'] }} · {{ __('production.call_time') }}: {{ str_replace('T', ' ', $assignment['call_at'] ?? __('production.not_set')) }} · {{ $assignment['timezone'] }}
            </p>
        @endforeach
        @if (! $person['assigned'])
            <p class="text-warning mt-2">{{ __('live-show.no_longer_assigned') }}</p>
        @endif
        @if ($person['warning'])
            <p class="text-warning mt-2">{{ __('live-show.unavailable') }}</p>
        @endif
        <p class="mt-2 text-sm tabular-nums">{{ __('live-show.arrival_time') }}: {{ $attendance['arrived'] }}</p>
        <p class="mt-1 text-sm tabular-nums">{{ __('live-show.briefing_time') }}: {{ $attendance['briefed'] }}</p>
    </div>
    @if (! $complete)
        @can('update', $event)
            <fieldset :disabled="requestFailed || ! online" wire:offline.attr="disabled" class="flex flex-wrap gap-3">
                @if ($person['arrived'] === null)
                    <x-button
                        variant="secondary"
                        wire:click="attendance('{{ $person['key'] }}', 'arrived', true)"
                        wire:loading.attr="disabled"
                    >{{ __('live-show.arrived') }}</x-button>
                @else
                    <x-button
                        variant="secondary"
                        wire:click="attendance('{{ $person['key'] }}', 'arrived', false)"
                        wire:confirm="{{ __('live-show.clear_confirm') }}"
                        wire:loading.attr="disabled"
                    >{{ __('live-show.clear_arrival') }}</x-button>
                @endif
                @if ($person['briefed'] === null)
                    <x-button
                        variant="secondary"
                        wire:click="attendance('{{ $person['key'] }}', 'briefed', true)"
                        wire:loading.attr="disabled"
                        :disabled="$person['arrived'] === null"
                    >{{ __('live-show.briefed') }}</x-button>
                @else
                    <x-button
                        variant="secondary"
                        wire:click="attendance('{{ $person['key'] }}', 'briefed', false)"
                        wire:loading.attr="disabled"
                    >{{ __('live-show.clear_briefing') }}</x-button>
                @endif
            </fieldset>
        @endcan
    @endif
</article>
