@props([
    'isCurrent' => false,
])

<div class="flex flex-col m-0 p-0">
    <a class="flex m-0 p-0 border border-transparent items-center grow" {{ $attributes }}>
        <span @class([
            'flex items-center grow text-sm hover:text-primary',
            'font-semibold text-primary' => $isCurrent,
            'font-medium text-gray-800' => !$isCurrent,
        ])>{{ $slot }}</span>
    </a>
</div>
