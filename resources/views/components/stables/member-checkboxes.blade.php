@props([
    'heading',
    'members',
])

@php
    $model = (string) $attributes->get('wire:model');
    $errorId = "{$model}-error";
    $errorMessages = collect($errors->get($model))->merge($errors->get("{$model}.*"))->flatten()->unique()->all();
@endphp

@if ($members->isNotEmpty())
    <fieldset class="space-y-2" @if ($errorMessages !== []) aria-describedby="{{ $errorId }}" @endif>
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

        @if ($errorMessages !== [])
            <div id="{{ $errorId }}" class="text-ringside-signal-soft space-y-1 text-sm" role="alert">
                @foreach ($errorMessages as $message)
                    <p>{{ $message }}</p>
                @endforeach
            </div>
        @endif
    </fieldset>
@endif
