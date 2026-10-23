@props([
    'name' => null,
    'label' => null,
    'description' => null,
    'variant' => 'block',
    'appearance' => 'default',
    'type' => 'text',
    'size' => 'md',               // 'sm', 'md' (default), 'lg'
    'initialFocus' => false,
])

@php
    $field = \App\View\Forms\FormFieldContext::from($name, $attributes, $errors);
    $fieldName = $field->name;
    $inputId = $field->id;
    $describedBy = $field->describedBy;

    // Build input classes matching .kt-input specifications
    $inputClasses = collect([
        // Base classes - matching .kt-input
        'block w-full appearance-none outline-none',
        'border border-solid border-outline bg-surface-panel text-foreground aria-invalid:border-error',
        'rounded-none transition-colors',
        'placeholder:text-muted-foreground',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-foreground',
        // Reserve space for the password visibility button at each control size.
        $size === 'sm' ? ($type === 'password' ? 'h-7 pl-2.5 pr-8 text-xs' : 'h-7 px-2.5 text-xs') : null,
        $size === 'md' ? ($type === 'password' ? 'h-8.5 pl-3 pr-10 text-2sm' : 'h-8.5 px-3 text-2sm') : null,
        $size === 'lg' ? ($type === 'password' ? 'h-10 pl-4 pr-12 text-sm' : 'h-10 px-4 text-sm') : null,
    ])->filter()->implode(' ');

    if ($appearance === 'ringside') {
        $inputClasses = 'block min-h-14 w-full min-w-0 appearance-none rounded-none border border-outline bg-surface-panel px-4 py-3 text-base leading-normal text-foreground caret-signal placeholder:text-muted-foreground focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-foreground aria-invalid:border-error';

        if ($type === 'password') {
            $inputClasses .= ' pr-14';
        }
    }

    $toggleClasses = $appearance === 'ringside'
        ? 'absolute inset-y-0 right-0 flex w-12 items-center justify-center text-muted-foreground hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-foreground'
        : 'text-muted-foreground absolute inset-y-0 right-0 flex items-center justify-center pr-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current';

    $showPasswordLabel = __($fieldName === 'password_confirmation' ? 'auth-forms.show_password_confirmation' : 'auth-forms.show_password');
    $hidePasswordLabel = __($fieldName === 'password_confirmation' ? 'auth-forms.hide_password_confirmation' : 'auth-forms.hide_password');

    // Forward all attributes except field-specific ones
    $inputAttributes = $attributes->except(['label', 'description', 'variant', 'name', 'size', 'appearance', 'aria-describedby', 'aria-invalid']);

    // Focus the field once a modal has opened (the dialog first focuses its close button), unless the user has
    // already moved into another field.
    if ($initialFocus) {
        $inputAttributes = $inputAttributes->merge([
            'data-initial-focus' => true,
            'x-data' => '',
            'x-init' => "setTimeout(() => document.activeElement?.matches('input, select, textarea') || \$el.focus(), 50)",
        ]);
    }
@endphp

@if ($appearance === 'ringside' && $type === 'password')
    <div class="relative">
        <input {{ $inputAttributes->merge(['type' => 'password', 'name' => $fieldName, 'id' => $inputId, 'class' => $inputClasses, 'aria-invalid' => $field->hasError ? 'true' : null, 'aria-describedby' => $describedBy ?: null]) }} />
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
        :required="(bool) $attributes->get('required', false)"
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
                            'aria-invalid' => $field->hasError ? 'true' : null,
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
                    'aria-invalid' => $field->hasError ? 'true' : null,
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
                'aria-invalid' => $field->hasError ? 'true' : null,
                'aria-describedby' => $describedBy ?: null,
            ])
        }} />
    @endif
@endif
