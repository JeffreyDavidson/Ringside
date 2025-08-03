@props(['subMenu' => null, 'variant' => 'default'])

@php
    $classes = [
        'flex flex-col m-0 p-0',
        'group' => $variant === 'sidebar'
    ];
@endphp

<div {{ $attributes->merge(['class' => collect($classes)->filter()->implode(' ')]) }}>
    {{ $slot }}
    @if ($subMenu)
        <x-menu.menu-accordian class="open ? 'show' : 'hidden'" x-show="open">
            {{ $subMenu }}
        </x-menu.menu-accordian>
    @endif
</div>
