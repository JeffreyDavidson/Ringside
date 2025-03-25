@aware(['inGrid' => false])

<div
    {{ $attributes->class(['grow'])->class([
        'py-5 ps-7.5 pe-7.5' => !$inGrid,
        'p-0' => $inGrid,
    ]) }}>
    {{ $slot }}
</div>
