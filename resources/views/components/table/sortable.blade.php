@props(['column', 'sortCol', 'sortAsc'])

<th
    wire:click="sortBy('{{ $column }}')"
    @class([
        'table-sort-asc' => $sortCol === $column && $sortAsc,
        'table-sort-desc' => $sortCol === $column && ! $sortAsc,
        'flex items-center gap-2 group'
    ])
>
    {{ $slot }}
</th>
