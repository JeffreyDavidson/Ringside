@aware(['inGrid' => false])

<div
    {{ $attributes->merge(['class' => 'flex min-h-14 items-center justify-between border-b border-solid border-gray-200 py-3'])->class([
        'ps-7.5 pe-7.5' => !$inGrid,
        'ps-5 pe-5' => $inGrid,
    ]) }}>
    {{ $slot }}
</div>
