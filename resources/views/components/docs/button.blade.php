@props([
    'isActive' => false,
    'tabToggle' => '',
    'withIcon' => false,
    'size' => 'default',
])

<a {{ $attributes->class([
    'inline-flex items-center cursor-pointer leading-none rounded-md outline-none h-8',
    'active' => $isActive,
]) }}
    data-tab-toggle="#default_1" href="#">
    {{ $slot }}
</a>
