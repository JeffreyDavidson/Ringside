@aware(['inGrid' => false])

<table {{ $attributes->class([
    'border border-solid border-gray-200' => !$inGrid,
    'b-0' => $inGrid,
])->class(['table']) }}>
    {{ $slot }}
</table>
