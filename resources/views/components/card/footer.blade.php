@aware(['inGrid' => false])

<div
    {{ $attributes->class([
            'ps-7.5 pe-7.5' => !$inGrid,
            'ps-5 pe-5' => $inGrid,
        ])->class(['flex items-center md:justify-between border-t border-solid border-gray-200 py-3']) }}>
    {{ $slot }}
</div>
