@props([
    'heading',
    'members',
])

@php
    $model = (string) $attributes->get('wire:model');
@endphp

@if ($members->isNotEmpty())
    <fieldset class="space-y-2">
        <legend class="text-ringside-ink text-sm font-semibold">{{ $heading }}</legend>

        <ul class="divide-ringside-line border-ringside-line divide-y border">
            @foreach ($members as $member)
                @php
                    $inputId = "{$model}-{$member['id']}";
                    $unavailability = $member['unavailability'] ?? null;
                @endphp

                <li wire:key="{{ $inputId }}" class="flex items-center gap-3 px-4 py-2.5">
                    <input
                        type="checkbox"
                        id="{{ $inputId }}"
                        value="{{ $member['id'] }}"
                        {{ $attributes->whereStartsWith('wire:model') }}
                        @disabled($unavailability !== null)
                        class="accent-ringside-red focus-visible:outline-ringside-white size-4 shrink-0 focus-visible:outline-2 focus-visible:outline-offset-2"
                    />
                    <label for="{{ $inputId }}" class="text-ringside-ink flex-1 text-sm">{{ $member['name'] }}</label>
                    @if ($unavailability !== null)
                        <span class="text-ringside-muted text-xs">{{ $unavailability->label() }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </fieldset>
@endif
