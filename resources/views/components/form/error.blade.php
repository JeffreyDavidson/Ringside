@props([
    'name' => null,
])

@if (is_string($name) && $errors->has($name))
    <div
        {{
            $attributes->merge([
                'class' => 'text-sm text-ringside-signal-soft mt-1 flex items-start gap-1.5',
                'role' => 'alert',
                'aria-live' => 'polite',
            ])
        }}
        data-form-error
    >
        <x-heroicon-s-exclamation-circle class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
        <span class="font-medium">{{ $errors->first($name) }}</span>
    </div>
@endif
