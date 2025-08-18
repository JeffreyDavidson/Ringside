@aware([
    'isDefault' => false,
])

@props([
    'icon' => ''
])

<span {{ $attributes->merge(['class' => 'flex items-center shrink-0'])->class([
    'me-2.5' => $isDefault,
]) }}>
    @if($icon)
        <i class="{{ $icon }} text-base"></i>
    @endif
</span>
