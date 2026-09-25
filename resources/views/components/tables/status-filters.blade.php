@props(['metadata', 'label', 'allLabel', 'selected' => ''])

@php
    $options = [['value' => '', 'label' => $allLabel, 'count' => $metadata['total']], ...$metadata['statuses']];
@endphp

<div class="border-ringside-line border-b" data-test="roster-status-filters">
    <div class="hidden flex-wrap gap-x-1 px-3 xl:flex" role="group" aria-label="{{ $label }}">
        @foreach ($options as $option)
            <button
                type="button"
                wire:key="status-{{ $option['value'] ?: 'all' }}"
                wire:click="$set('filterValues.status', '{{ $option['value'] }}')"
                aria-pressed="{{ $selected === $option['value'] ? 'true' : 'false' }}"
                @class([
                    'inline-flex min-h-14 cursor-pointer items-center gap-2 border-b-2 px-3 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-ringside-ink',
                    'border-ringside-signal text-ringside-ink font-semibold' => $selected === $option['value'],
                    'border-transparent text-ringside-muted hover:text-ringside-ink hover:bg-ringside-surface-hover' => $selected !== $option['value'],
                ])
            >
                {{ $option['label'] }}
                <span class="bg-ringside-surface-hover text-ringside-muted min-w-6 px-1.5 py-0.5 text-center text-xs tabular-nums">{{ $option['count'] }}</span>
            </button>
        @endforeach
    </div>
    <div class="flex items-center gap-3 px-4 py-3 xl:hidden">
        <label for="roster-status" class="text-ringside-muted text-sm">{{ __('core.status') }}</label>
        <div class="relative min-w-0 grow">
            <select
                id="roster-status"
                wire:model.live="filterValues.status"
                class="border-ringside-line bg-ringside-surface text-ringside-ink focus-visible:outline-ringside-ink min-h-11 w-full appearance-none border py-2 ps-3 pe-10 text-sm focus-visible:outline-2"
            >
                @foreach ($options as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }} ({{ $option['count'] }})</option>
                @endforeach
            </select>
            <x-heroicon-o-chevron-down
                class="text-ringside-muted pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2"
                aria-hidden="true"
            />
        </div>
    </div>
</div>
