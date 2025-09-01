@props([
    'label' => null,
    'description' => null,
    'variant' => 'block',
    'name' => null,
])

@php
// Build wrapper classes based on variant
$classes = collect()
    ->when($variant === 'block', fn($classes) => $classes->add('flex flex-col gap-1'))
    ->when($variant === 'inline', fn($classes) => $classes->merge([
        'grid gap-x-3 gap-y-1.5',
        'grid-cols-[1fr_auto]',
        '[&>[data-form-control]~[data-form-description]]:row-start-2 [&>[data-form-control]~[data-form-description]]:col-start-2',
        '[&>[data-form-control]~[data-form-error]]:col-span-2 [&>[data-form-control]~[data-form-error]]:mt-1',
        '[&>[data-form-label]~[data-form-control]]:row-start-1 [&>[data-form-label]~[data-form-control]]:col-start-2',
    ]))
    ->implode(' ');
@endphp

<div {{ $attributes->merge(['class' => $classes]) }} data-form-field>
    {{-- Label Section --}}
    @if($label)
        <x-form.label data-form-label>{{ $label }}</x-form.label>
    @endif

    {{-- Form Control Section --}}
    {{ $slot }}

    {{-- Description Section --}}
    @if($description)
        <x-form.description data-form-description>{{ $description }}</x-form.description>
    @endif

    {{-- Error Section --}}
    @if($name)
        @error($name)
            <div data-form-error>
                <x-form.error :name="$name" />
            </div>
        @enderror
    @endif
</div>
