@props([
    'label' => null,
    'group' => null,
    'kind',
    'multiple' => false,
    'labels' => [],
    'limit' => \App\Livewire\Matches\Support\BookableRosterSearch::LIMIT,
    'placeholder' => null,
    'errorName' => null,
    'required' => false,
])

@php
    $field = \App\View\Forms\FormFieldContext::from(null, $attributes, $errors);
    $fieldName = $field->name;
    $errorName ??= $fieldName;
    $hasError = $errors->has($errorName) || $errors->has("{$errorName}.*");
    $errorMessage = $errors->has($errorName) ? $errors->first($errorName) : $errors->first("{$errorName}.*");
    $hintId = "{$fieldName}-hint";
    $errorId = "{$fieldName}-error";
    $describedBy = $hasError ? "{$hintId} {$errorId}" : $hintId;
    $accessibleLabel = trim("{$group} {$label}");

    $inputClasses = 'block w-full appearance-none outline-none border border-solid border-ringside-outline bg-ringside-surface-panel text-ringside-ink aria-invalid:border-ringside-signal-soft rounded-none transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ringside-white h-[calc(var(--spacing)*8.5)] px-[calc(var(--spacing)*3)] text-2sm';
@endphp

<div
    x-data="rosterCombobox({
        path: @js($fieldName),
        kind: @js($kind->value),
        multiple: @js($multiple),
        labels: @js($labels),
        limit: @js($limit),
        messages: @js([
            'searching' => __('core.combobox.searching'),
            'empty' => __('core.combobox.empty'),
            'capped' => __('core.combobox.capped', ['count' => $limit]),
            'one' => __('core.combobox.one_result'),
            'many' => __('core.combobox.results'),
            'remove' => __('core.combobox.remove'),
            'unknown' => __('core.combobox.unknown'),
        ]),
    })"
    class="flex min-w-0 flex-col gap-1"
    data-form-field
    data-roster-combobox="{{ $fieldName }}"
>
    <div
        class="relative"
        x-combobox
        x-model="selected"
        :multiple="{{ $multiple ? 'true' : 'false' }}"
        @keydown.escape.capture="passEscapeWhenClosed($event, $combobox.isOpen)"
        @keydown.enter.capture="ignoreEnterWhileLoading($event)"
    >
        @if ($label)
            <x-form.label x-combobox:label class="mb-1" :required="$required">
                @if ($group)
                    <span class="sr-only">{{ $group }}</span>
                @endif

                {{ $label }}
            </x-form.label>
        @endif

        <div class="relative">
            <input
                x-combobox:input
                type="text"
                autocomplete="off"
                placeholder="{{ $placeholder ?? __('core.combobox.placeholder') }}"
                :display-value="(value) => displayValue(value)"
                @focus="showAll()"
                @keydown="startNewTermOverLabel($event)"
                @click="$combobox.isOpen || __open()"
                @input="startSearch()"
                @input.debounce.250ms="search($event.target.value)"
                @change="$event.target.value === '' && showAll()"
                @class([$inputClasses, 'pr-9'])
                data-field="{{ $fieldName }}"
                aria-invalid="{{ $hasError ? 'true' : 'false' }}"
                aria-required="{{ $required ? 'true' : 'false' }}"
                aria-describedby="{{ $describedBy }}"
            />
            <button
                x-combobox:button
                type="button"
                class="text-ringside-muted absolute inset-y-0 right-0 flex items-center px-2"
                aria-label="{{ __('core.combobox.show_options', ['label' => $accessibleLabel ?: __('core.combobox.options')]) }}"
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
                    class="text-ringside-ink text-2sm flex cursor-pointer items-center justify-between gap-2 px-3 py-2"
                    :class="$comboboxOption.isActive ? 'bg-ringside-surface-deep' : ''"
                >
                    <span class="min-w-0 break-words" x-text="option.name"></span>
                    <x-heroicon-s-check
                        x-show="$comboboxOption.isSelected"
                        class="size-4 shrink-0"
                        aria-hidden="true"
                    />
                </li>
            </template>
            <li x-show="loading" role="presentation" class="text-ringside-muted text-2sm px-3 py-2">
                {{ __('core.combobox.searching') }}
            </li>
            <li
                x-show="loaded && ! loading && options.length === 0"
                role="presentation"
                class="text-ringside-muted text-2sm px-3 py-2"
            >
                {{ __('core.combobox.empty') }}
            </li>
            <li
                x-show="! loading && options.length >= limit"
                role="presentation"
                class="text-ringside-muted border-ringside-outline border-t border-solid px-3 py-2 text-xs"
            >
                {{ __('core.combobox.capped', ['count' => $limit]) }}
            </li>
        </ul>
    </div>

    <p id="{{ $hintId }}" class="sr-only">
        {{ $multiple ? __('core.combobox.hint_multiple') : __('core.combobox.hint') }}
    </p>
    <p class="sr-only" role="status" aria-live="polite" data-test="combobox-status" x-text="status"></p>

    @if ($multiple)
        <ul class="flex min-w-0 flex-wrap gap-1.5" x-show="selectedIds().length > 0" data-test="selected-chips">
            <template x-for="(id, index) in selectedIds()" :key="id">
                <li class="border-ringside-line bg-ringside-surface-panel text-ringside-ink text-2sm flex max-w-full min-w-0 items-center gap-1 border border-solid py-0.5 pr-0.5 pl-2">
                    <span class="min-w-0 truncate" x-text="labelFor(id)" :title="labelFor(id)"></span>
                    <button
                        type="button"
                        class="text-ringside-muted hover:text-ringside-ink inline-flex size-6 shrink-0 items-center justify-center"
                        :aria-label="removeLabelFor(id)"
                        @click="remove(id, index)"
                    >
                        <x-heroicon-s-x-mark class="size-4" aria-hidden="true" />
                    </button>
                </li>
            </template>
        </ul>
    @endif

    @if ($hasError)
        <div id="{{ $errorId }}" data-form-error>
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
