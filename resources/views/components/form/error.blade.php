@props([
    'name' => null,
    'showIcon' => false,
])

@error($name)
    <div {{ $attributes->merge([
        'class' => 'text-sm text-destructive mt-1 flex items-start gap-1',
        'role' => 'alert',
        'aria-live' => 'polite'
    ]) }} data-form-error>
        @if($showIcon)
            <svg class="w-4 h-4 mt-0.5 text-destructive flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
        @endif
        <span class="font-medium">{{ $message }}</span>
    </div>
@enderror