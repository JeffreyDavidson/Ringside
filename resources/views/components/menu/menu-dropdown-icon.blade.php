@aware([
    'isDefault' => false,
])

@props([
    'icon' => ''
])

<span {{ $attributes->merge(['class' => 'flex items-center shrink-0'])->class([
    'me-2.5' => $isDefault,
]) }}>
    <i class="ki-filled {{ $icon }}"></i>
</span>
