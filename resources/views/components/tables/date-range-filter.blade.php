@props(['idPrefix', 'filterKey', 'label', 'fromLabel', 'toLabel'])

<fieldset class="grid gap-2">
    <legend class="text-ringside-muted text-xs font-medium">{{ $label }}</legend>
    <div data-test="{{ $idPrefix }}-date-range-grid" class="grid grid-cols-1 gap-3 min-[380px]:grid-cols-2">
        <div class="grid gap-1">
            <label for="{{ $idPrefix }}-date-from" class="text-ringside-muted text-xs"> {{ $fromLabel }} </label>
            <input
                id="{{ $idPrefix }}-date-from"
                type="date"
                wire:model.live="filterValues.{{ $filterKey }}.minDate"
                class="border-ringside-line bg-ringside-surface text-ringside-ink focus-visible:outline-ringside-ink min-h-11 min-w-0 border px-2 text-sm focus-visible:outline-2"
            />
        </div>
        <div class="grid gap-1">
            <label for="{{ $idPrefix }}-date-to" class="text-ringside-muted text-xs"> {{ $toLabel }} </label>
            <input
                id="{{ $idPrefix }}-date-to"
                type="date"
                wire:model.live="filterValues.{{ $filterKey }}.maxDate"
                class="border-ringside-line bg-ringside-surface text-ringside-ink focus-visible:outline-ringside-ink min-h-11 min-w-0 border px-2 text-sm focus-visible:outline-2"
            />
        </div>
    </div>
</fieldset>
