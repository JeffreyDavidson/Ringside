@props([
    'label' => null,
    'kind',
    'multiple' => false,
    'labels' => [],
    'placeholder' => 'Search by name',
])

@php
    $field = \App\View\Forms\FormFieldContext::from(null, $attributes, $errors);
    $fieldName = $field->name;
    $hasError = $field->hasError || $errors->has("{$fieldName}.*");
    $errorMessage = $field->hasError ? $errors->first($fieldName) : $errors->first("{$fieldName}.*");

    $inputClasses = 'block w-full appearance-none outline-none border border-solid border-ringside-outline bg-ringside-surface-panel text-ringside-ink aria-invalid:border-ringside-signal-soft rounded-none transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ringside-white h-[calc(var(--spacing)*8.5)] px-[calc(var(--spacing)*3)] text-2sm';
@endphp

<div
    x-data="rosterCombobox({
        path: @js($fieldName),
        kind: @js($kind->value),
        multiple: @js($multiple),
        labels: @js($labels),
    })"
    class="flex flex-col gap-1"
    data-form-field
    data-roster-combobox="{{ $fieldName }}"
>
    <div class="relative" x-combobox x-model="selected" :multiple="{{ $multiple ? 'true' : 'false' }}">
        @if ($label)
            <x-form.label x-combobox:label class="mb-1">{{ $label }}</x-form.label>
        @endif

        <div class="relative">
            <input
                x-combobox:input
                type="text"
                autocomplete="off"
                placeholder="{{ $placeholder }}"
                :display-value="(id) => labelFor(id)"
                @focus="loadInitial()"
                @change.debounce.250ms="search($event.target.value)"
                @class([$inputClasses, 'pr-9'])
                data-field="{{ $fieldName }}"
                aria-invalid="{{ $hasError ? 'true' : 'false' }}"
            />
            <button
                x-combobox:button
                type="button"
                class="text-ringside-muted absolute inset-y-0 right-0 flex items-center px-2"
                aria-label="Show {{ $label ?? 'options' }}"
            >
                <x-heroicon-s-chevron-up-down class="size-4" aria-hidden="true" />
            </button>
        </div>

        <ul
            x-combobox:options
            class="border-ringside-outline bg-ringside-surface-panel absolute z-30 mt-1 max-h-56 w-full overflow-auto border border-solid py-1 shadow-xl"
        >
            <template x-for="option in options" :key="option.id">
                <li
                    x-combobox:option
                    :value="option.id"
                    class="text-ringside-ink text-2sm flex cursor-pointer items-center justify-between px-3 py-2"
                    :class="$comboboxOption.isActive ? 'bg-ringside-surface-deep' : ''"
                >
                    <span x-text="option.name"></span>
                    <x-heroicon-s-check
                        x-show="$comboboxOption.isSelected"
                        class="size-4 shrink-0"
                        aria-hidden="true"
                    />
                </li>
            </template>
            <li
                x-show="loaded && ! loading && options.length === 0"
                role="presentation"
                class="text-ringside-muted text-2sm px-3 py-2"
            >
                No bookable matches
            </li>
        </ul>
    </div>

    @if ($multiple)
        <ul class="flex flex-wrap gap-1.5" x-show="selectedIds().length > 0" data-test="selected-chips">
            <template x-for="id in selectedIds()" :key="id">
                <li class="border-ringside-line bg-ringside-surface-panel text-ringside-ink text-2sm flex items-center gap-1.5 border border-solid px-2 py-1">
                    <span x-text="labelFor(id)"></span>
                    <button
                        type="button"
                        class="text-ringside-muted hover:text-ringside-ink"
                        :aria-label="`Remove ${labelFor(id)}`"
                        @click="remove(id)"
                    >
                        <x-heroicon-s-x-mark class="size-4" aria-hidden="true" />
                    </button>
                </li>
            </template>
        </ul>
    @endif

    @if ($hasError)
        <div id="{{ $fieldName }}-error" data-form-error>
            <div
                class="text-ringside-signal-soft mt-1 flex items-start gap-1.5 text-sm"
                role="alert"
                aria-live="polite"
            >
                <x-heroicon-s-exclamation-circle class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                <span class="font-medium">{{ $errorMessage }}</span>
            </div>
        </div>
    @endif
</div>
