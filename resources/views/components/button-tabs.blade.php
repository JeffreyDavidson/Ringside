@props([
    'size' => 'default',
])

<ul role="tablist" @keydown.right.prevent.stop="$focus.wrap().next()" @keydown.home.prevent.stop="$focus.first()"
    @keydown.page-up.prevent.stop="$focus.first()" @keydown.left.prevent.stop="$focus.wrap().prev()"
    @keydown.end.prevent.stop="$focus.last()" @keydown.page-down.prevent.stop="$focus.last()" role="tablist"
    {{ $attributes->class([
        'inline-flex items-center leading-none bg-gray-100 border border-solid border-gray-200 rounded-md',
        'h-8 p-[.188rem] gap-[.188rem]' => $size === 'sm',
        'h-10 p-1 gap-1' => $size === 'default',
        'h-12 p-[.313rem] gap-[.313rem]' => $size === 'lg',
    ]) }}>
    {{ $slot }}
</ul>
