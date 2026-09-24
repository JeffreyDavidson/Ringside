@props([
    'name' => null,
    'label' => null,
    'description' => null,
    'variant' => 'block',
    'appearance' => 'default',
    'type' => 'text',
    'size' => 'md',               // 'sm', 'md' (default), 'lg'
])

@php
    // Extract name from wire:model if not provided (Flux pattern)
    $fieldName = $name ?? $attributes->whereStartsWith('wire:model')->first();
    if ($fieldName !== null && ! is_string($fieldName)) {
        throw new \InvalidArgumentException('Form field names must be strings.');
    }
    if ($fieldName && str_contains($fieldName, '=')) {
        $fieldName = str($fieldName)->after('=')->trim('"\'')->toString();
    }

    // Generate ID
    $inputId = $attributes->get('id', $fieldName);
    $fieldErrorId = $inputId.'-error';
    $describedBy = collect(preg_split('/\s+/', trim((string) $attributes->get('aria-describedby'))))
        ->merge($fieldName && $errors->has($fieldName) ? [$fieldErrorId] : [])
        ->filter()
        ->unique()
        ->implode(' ');

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

    if ($appearance === 'ringside') {
        $inputClasses = 'block min-h-14 w-full min-w-0 appearance-none rounded-none border border-ringside-outline bg-ringside-surface-panel px-4 py-3 text-base leading-normal text-ringside-ink caret-ringside-signal placeholder:text-ringside-muted focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ringside-white aria-invalid:border-ringside-signal-soft';
        if ($type === 'password') {
            $inputClasses .= ' pr-14';
        }
    }

    $toggleClasses = $appearance === 'ringside'
        ? 'absolute inset-y-0 right-0 flex w-12 items-center justify-center text-ringside-muted hover:text-ringside-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ringside-white'
        : 'text-muted-foreground absolute inset-y-0 right-0 flex items-center justify-center pr-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current';

    $showPasswordLabel = __($fieldName === 'password_confirmation' ? 'auth-forms.show_password_confirmation' : 'auth-forms.show_password');
    $hidePasswordLabel = __($fieldName === 'password_confirmation' ? 'auth-forms.hide_password_confirmation' : 'auth-forms.hide_password');

    // Forward all attributes except field-specific ones
    $inputAttributes = $attributes->except(['label', 'description', 'variant', 'name', 'size', 'appearance', 'aria-describedby', 'aria-invalid']);
@endphp

@if ($appearance === 'ringside' && $type === 'password')
    <div class="relative">
        <input {{ $inputAttributes->merge(['type' => 'password', 'name' => $fieldName, 'id' => $inputId, 'class' => $inputClasses, 'aria-invalid' => $fieldName && $errors->has($fieldName) ? 'true' : null, 'aria-describedby' => $describedBy ?: null]) }} />
        <button
            type="button"
            class="{{ $toggleClasses }}"
            data-password-toggle
            data-show-label="{{ $showPasswordLabel }}"
            data-hide-label="{{ $hidePasswordLabel }}"
            aria-label="{{ $showPasswordLabel }}"
            aria-controls="{{ $inputId }}"
            hidden
        >
            <span data-password-show><x-heroicon-s-eye class="size-4" /></span>
            <span data-password-hide hidden><x-heroicon-s-eye-slash class="size-4" /></span>
        </button>
    </div>
@elseif ($label || $description)
    {{-- Shorthand mode: auto-wrap in field (Flux pattern) --}}
    <x-form.with-field
        :label="$label"
        :description="$description"
        :variant="$variant"
        :name="$fieldName"
        :id="$inputId"
    >
        @if ($type === 'password')
            <div class="relative" x-data="{ showPassword: false }">
                <input
                    {{
                        $inputAttributes->merge([
                            'type' => $type,
                            'name' => $fieldName,
                            'id' => $inputId,
                            'class' => $inputClasses,
                            'aria-invalid' => $fieldName && $errors->has($fieldName) ? 'true' : null,
                            'aria-describedby' => $describedBy ?: null,
                        ])
                    }}
                    :type="showPassword ? 'text' : 'password'"
                />
                <button
                    type="button"
                    class="{{ $toggleClasses }}"
                    aria-label="{{ $showPasswordLabel }}"
                    aria-controls="{{ $inputId }}"
                    :aria-label="showPassword ? @js($hidePasswordLabel) : @js($showPasswordLabel)"
                    @click="showPassword = ! showPassword"
                >
                    <span x-show="! showPassword">
                        <x-heroicon-s-eye class="size-4" />
                    </span>
                    <span x-show="showPassword" style="display: none">
                        <x-heroicon-s-eye-slash class="size-4" />
                    </span>
                </button>
            </div>
        @else
            <input {{
                $inputAttributes->merge([
                    'type' => $type,
                    'name' => $fieldName,
                    'id' => $inputId,
                    'class' => $inputClasses,
                    'aria-invalid' => $fieldName && $errors->has($fieldName) ? 'true' : null,
                    'aria-describedby' => $describedBy ?: null,
                ])
            }} />
        @endif
    </x-form.with-field>
@else
    {{-- Verbose mode: just the input --}}
    @if ($type === 'password')
        <div class="relative" x-data="{ showPassword: false }">
            <input
                {{
                    $inputAttributes->merge([
                        'type' => $type,
                        'name' => $fieldName,
                        'id' => $inputId,
                        'class' => $inputClasses,
                    ])
                }}
                :type="showPassword ? 'text' : 'password'"
            />
            <button
                type="button"
                class="{{ $toggleClasses }}"
                aria-label="{{ $showPasswordLabel }}"
                aria-controls="{{ $inputId }}"
                :aria-label="showPassword ? @js($hidePasswordLabel) : @js($showPasswordLabel)"
                @click="showPassword = ! showPassword"
            >
                <span x-show="! showPassword">
                    <x-heroicon-s-eye class="size-4" />
                </span>
                <span x-show="showPassword" style="display: none">
                    <x-heroicon-s-eye-slash class="size-4" />
                </span>
            </button>
        </div>
    @else
        <input {{
            $inputAttributes->merge([
                'type' => $type,
                'name' => $fieldName,
                'id' => $inputId,
                'class' => $inputClasses,
                'aria-invalid' => $fieldName && $errors->has($fieldName) ? 'true' : null,
                'aria-describedby' => $describedBy ?: null,
            ])
        }} />
    @endif
@endif
