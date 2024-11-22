@aware(['inGrid' => false])
@props(['border' => true])

<div
    {{ $attributes->class([
        'flex items-center justify-between py-3',
        'ps-7.5 pe-7.5' => !$inGrid,
        'ps-5 pe-5' => $inGrid,
        'border-t border-solid border-gray-200' => $border,
    ]) }}>
    {{ $slot }}
</div>
