@props(['column', 'sortCol', 'sortAsc'])

<button wire:click="sortBy('{{ $column }}')" {{ $attributes->merge(['class' => 'flex items-center gap-2 group']) }}>
    {{ $slot }}

    @if ($sortCol === $column)
        <div class="text-gray-400">
            @if ($sortAsc)
                <x-icon.chevron-up/>
            @else
                <x-icon.chevron-down/>
            @endif
        </div>
    @endif
</button>
