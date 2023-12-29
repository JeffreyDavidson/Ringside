<x-datatable>
    <x-slot name="head">
        <x-table.heading class="min-w-125px sorting_disabled">Manager Name</x-table.heading>
        <x-table.heading class="min-w-70px sorting_disabled">Date Joined</x-table.heading>
        <x-table.heading class="min-w-70px sorting_disabled">Date Left</x-table.heading>
    </x-slot>
    <x-slot name="body">
        @forelse ($managers as $manager)
            <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:key="row-{{ $manager->id }}">
                <x-table.cell>
                    <x-route-link
                        :route="route('managers.show', $manager)"
                        label="{{ $manager->full_name }}"
                    />
                </x-table.cell>
                <x-table.cell>{{ $manager->pivot->hired_at->toDateString() }}</x-table.cell>
                <x-table.cell>{{ $manager->pivot->left_at->toDateString() }}</x-table.cell>
            </x-table.row>
        @empty
            <x-table.row-no-data colspan="3"/>
        @endforelse
    </x-slot>
    <x-slot name="footer">
        <x-table.footer :collection="$managers" />
    </x-slot>
</x-datatable>
