<x-card>
    <x-card.header class="pt-6">
        <x-card.title>
            <x-search resource="Venues" />
        </x-card.title>

        <x-card.toolbar>
            <x-card.toolbar.actions>
                <x-buttons.create route="{{ route('venues.create') }}" resource="Venue" />
            </x-card.toolbar.actions>
        </x-card.toolbar>
    </x-card.header>

    <x-card.body class="pt-0">
        <x-table.wrapper>
            <x-table>
                <x-table.head>
                    <x-table.heading class="w-10px pe-2 sorting_disabled"><x-form.inputs.checkbox wire:model="selectPage" /></x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('name')" :direction="$sorts['name'] ?? null" class="min-w-125px sorting">Venue Name</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">Address</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">City</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">State</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">Zip Code</x-table.heading>
                    <x-table.heading class="text-end min-w-70px sorting_disabled">Actions</x-table.heading>
                </x-table.head>
                <x-table.body>
                    @forelse ($venues as $venue)
                        <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:loading.class.delay="opacity-50" wire:key="row-{{ $venue->id }}">
                            <x-table.cell><x-form.inputs.checkbox wire:model="selected" value="{{ $venue->id }}" /></x-table.cell>
                            <x-table.cell><a class="mb-1 text-gray-800 text-hover-primary" href="{{ route('venues.show', $venue) }}">{{ $venue->name }}</a></x-table.cell>
                            <x-table.cell>{{ $venue->street_address }}</x-table.cell>
                            <x-table.cell>{{ $venue->city }}</x-table.cell>
                            <x-table.cell>{{ $venue->state }}</x-table.cell>
                            <x-table.cell>{{ $venue->zip }}</x-table.cell>
                            <x-table.cell class="text-end">
                                @include('livewire.venues.partials.action-cell')
                            </x-table.cell>
                        </x-table.row>
                    @empty
                        <x-table.row-no-data colspan="7"/>
                    @endforelse
                </x-table.body>
                <x-table.foot/>
            </x-table>
            <x-table.footer :collection="$venues"/>
        </x-table.wrapper>
    </x-card.body>
</x-card>
