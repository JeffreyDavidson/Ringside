@aware([
    'size' => ''
])

<button {{ $attributes->merge(['class' => 'inline-flex items-center cursor-pointer leading-4 rounded-md font-medium border border-solid border-transparent'])
    ->class([
        'h-10 text-2sm ps-4 pe-4 gap-1.5' => !isset($size),
        'h-8 text-xs ps-3 pe-3 gap-[.275rem]' => isset($size) && $size === 'sm'
    ]) }}>
    {{ $slot }}
</button>
