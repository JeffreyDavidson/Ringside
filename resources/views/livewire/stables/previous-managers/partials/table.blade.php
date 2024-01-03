<x-datatable>
    <x-slot name="head">
        <x-table.heading
            sortable
            multi-column
            wire:click="sortBy('full_name')"
            :direction="$sorts['full_name'] ?? null"
            class="min-w-125px sorting">Manager Name</x-table.heading>
        <x-table.heading class="min-w-70px sorting_disabled">Date Hired</x-table.heading>
        <x-table.heading class="min-w-70px sorting_disabled">Date Left</x-table.heading>
    </x-slot>
    <x-slot name="body">
        @forelse ($previousManagers as $previousManager)
            <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:key="row-{{ $previousManager->id }}">
                <x-table.cell>
                    <x-route-link
                        :route="route('managers.show', $previousManager)"
                        label="{{ $previousManager->full_name }}"
                    />
                </x-table.cell>
                <x-table.cell>{{ $previousManager->pivot->joined_at->toDateString() }}</x-table.cell>
                <x-table.cell>{{ $previousManager->pivot->left_at->toDateString() }}</x-table.cell>
            </x-table.row>
        @empty
            <x-table.row-no-data colspan="3"/>
        @endforelse
    </x-slot>
    <x-slot name="footer">
        <x-table.footer :collection="$previousManagers" />
    </x-slot>
</x-datatable>
