@props([
    'isActive' => false,
])

<a {{ $attributes }} @class([
    'flex items-center grow cursor-pointer text-sm',
    'text-gray-900 font-medium' => $isActive,
    'text-gray-800' => !$isActive,
])>
    {{ $slot }}
</a>
