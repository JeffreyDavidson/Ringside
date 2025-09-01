@props([
    'name' => null,
    'label' => null,
    'description' => null,
    'variant' => 'block',
    'type' => 'text',
    'size' => 'md',               // 'sm', 'md' (default), 'lg'
])

@php
// Extract name from wire:model if not provided (Flux pattern)
$fieldName = $name ?? $attributes->whereStartsWith('wire:model')->first();
if ($fieldName && str_contains($fieldName, '=')) {
    $fieldName = str($fieldName)->after('=')->trim('"\'')->toString();
}

// Generate ID
$inputId = $attributes->get('id', $fieldName);

// Build input classes matching .kt-input specifications
$inputClasses = collect([
    // Base classes - matching .kt-input
    'block w-full appearance-none outline-none',
    'border border-solid border-[var(--input)] bg-background text-foreground',
    'rounded-[calc(var(--radius)-2px)] shadow-[var(--tw-input-box-shadow)] transition-[color,box-shadow]',
    'placeholder-[var(--muted-foreground)]',
    'focus-visible:outline-none focus-visible:border-[var(--ring)] focus-visible:ring-2 focus-visible:ring-[color-mix(in_oklab,var(--ring)_30%,transparent)]',
    // Size variants using CSS variables (Metronic specifications) - add extra right padding for password fields
    $size === 'sm' ? ($type === 'password' ? 'h-[calc(var(--spacing)*7)] pl-[calc(var(--spacing)*2.5)] pr-[calc(var(--spacing)*8)] text-xs' : 'h-[calc(var(--spacing)*7)] px-[calc(var(--spacing)*2.5)] text-xs') : null,
    $size === 'md' ? ($type === 'password' ? 'h-[calc(var(--spacing)*8.5)] pl-[calc(var(--spacing)*3)] pr-[calc(var(--spacing)*10)] text-2sm' : 'h-[calc(var(--spacing)*8.5)] px-[calc(var(--spacing)*3)] text-2sm') : null,
    $size === 'lg' ? ($type === 'password' ? 'h-[calc(var(--spacing)*10)] pl-[calc(var(--spacing)*4)] pr-[calc(var(--spacing)*12)] text-sm' : 'h-[calc(var(--spacing)*10)] px-[calc(var(--spacing)*4)] text-sm') : null,
])->filter()->implode(' ');

// Forward all attributes except field-specific ones
$inputAttributes = $attributes->except(['label', 'description', 'variant', 'name', 'size']);
@endphp

@if($label || $description)
    {{-- Shorthand mode: auto-wrap in field (Flux pattern) --}}
    <x-form.with-field
        :label="$label"
        :description="$description"
        :variant="$variant"
        :name="$fieldName">
        @if($type === 'password')
            <div class="relative" x-data="{ showPassword: false }">
                <input
                    {{ $inputAttributes->merge([
                        'name' => $fieldName,
                        'id' => $inputId,
                        'class' => $inputClasses
                    ]) }}
                    :type="showPassword ? 'text' : 'password'" />
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex items-center justify-center pr-3 text-muted-foreground focus:outline-none"
                    @click="showPassword = !showPassword">
                    <span x-show="!showPassword">
                        <x-ui.icon name="eye" style="filled" size="md" />
                    </span>
                    <span x-show="showPassword">
                        <x-ui.icon name="eye-slash" style="filled" size="md" />
                    </span>
                </button>
            </div>
        @else
            <input
                {{ $inputAttributes->merge([
                    'type' => $type,
                    'name' => $fieldName,
                    'id' => $inputId,
                    'class' => $inputClasses
                ]) }} />
        @endif
    </x-form.with-field>
@else
    {{-- Verbose mode: just the input --}}
    @if($type === 'password')
        <div class="relative" x-data="{ showPassword: false }">
            <input
                {{ $inputAttributes->merge([
                    'name' => $fieldName,
                    'id' => $inputId,
                    'class' => $inputClasses
                ]) }}
                :type="showPassword ? 'text' : 'password'" />
            <button
                type="button"
                class="absolute inset-y-0 right-0 flex items-center justify-center pr-3 text-muted-foreground focus:outline-none"
                @click="showPassword = !showPassword">
                <span x-show="!showPassword">
                    <x-ui.icon name="eye" style="filled" size="md" />
                </span>
                <span x-show="showPassword">
                    <x-ui.icon name="eye-slash" style="filled" size="md" />
                </span>
            </button>
        </div>
    @else
        <input
            {{ $inputAttributes->merge([
                'type' => $type,
                'name' => $fieldName,
                'id' => $inputId,
                'class' => $inputClasses
            ]) }} />
    @endif
@endif
