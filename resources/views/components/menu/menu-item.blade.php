<div {{ $attributes->merge(['class' => 'flex flex-col m-0 p-0']) }}>
    {{ $slot }}
    @if (isset($subMenu))
        <x-menu.menu-accordian class="open ? 'show' : 'hidden'" x-show="open">
            {{ $subMenu }}
        </x-menu.menu-accordian>
    @endif
</div>
