<x-datatable>
    <x-slot name="head">
        <x-table.heading
            sortable
            multi-column
            wire:click="sortBy('name')"
            :direction="$sorts['name'] ?? null"
            class="min-w-125px sorting">Wrestler Name</x-table.heading>
        <x-table.heading class="min-w-70px sorting_disabled">Date Joined</x-table.heading>
        <x-table.heading class="min-w-70px sorting_disabled">Date Left</x-table.heading>
    </x-slot>
    <x-slot name="body">
        @forelse ($wrestlers as $wrestler)
            <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:key="row-{{ $wrestler->id }}">
                <x-table.cell>
                    <x-route-link
                        :route="route('wrestlers.show', $wrestler)"
                        label="{{ $wrestler->name }}"
                    />
                </x-table.cell>
                <x-table.cell>{{ $wrestler->pivot->joined_at->toDateString() }}</x-table.cell>
                <x-table.cell>{{ $wrestler->pivot->left_at->toDateString() }}</x-table.cell>
            </x-table.row>
        @empty
            <x-table.row-no-data colspan="3"/>
        @endforelse
    </x-slot>
    <x-slot name="footer">
        <x-table.footer :collection="$wrestlers" />
    </x-slot>
</x-datatable>
