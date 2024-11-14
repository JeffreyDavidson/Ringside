@aware(['inGrid' => false])

<div
    {{ $attributes->class([
            'py-5' => !$inGrid,
            'p-0' => $inGrid,
        ])->class(['grow ps-7.5 pe-7.5']) }}>
    {{ $slot }}
</div>
