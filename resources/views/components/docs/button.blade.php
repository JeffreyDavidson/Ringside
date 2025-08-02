@props([
    'isActive' => false,
    'tabToggle' => '',
    'withIcon' => false,
    'size' => 'default',
])

<a {{ $attributes->class([
    'inline-flex items-center cursor-pointer leading-none rounded-md outline-none h-8 px-2.5 text-sm font-medium transition-all duration-200',
    'bg-white text-gray-900 shadow-sm' => $isActive,
    'text-gray-600 hover:text-gray-900' => !$isActive,
]) }}
    href="#">
    {{ $slot }}
</a>
