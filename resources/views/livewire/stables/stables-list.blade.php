<x-card class="pt-6">
    <x-card.header>
        <x-card.title>
            <x-search resource="Stables" />
        </x-card.title>

        <x-card.toolbar>
            <card.toolbar.actions>
                <x-buttons.create route="{{ route('stables.create') }}" resource="Stable" />
            </card.toolbar.actions>
        </x-card.toolbar>
    </x-card.header>

    <x-card.body class="pt-0">
        <x-table.wrapper>
            <x-table>
                <x-table.head>
                    <x-table.heading class="w-10px pe-2 sorting_disabled"><x-stables.index.check-all/></x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('name')" :direction="$sorts['name'] ?? null" class="min-w-125px sorting">Stable Name</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('status')" :direction="$sorts['status'] ?? null" class="min-w-125px sorting">Status</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('start_date')" :direction="$sorts['start_date'] ?? null" class="min-w-125px sorting">Start Date</x-table.heading>
                    <x-table.heading class="text-end min-w-70px sorting_disabled">Actions</x-table.heading>
                </x-table.head>
                <x-table.body>
                    @forelse ($stables as $stable)
                        <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:loading.class.delay="opacity-50" wire:key="row-{{ $stable->id }}">
                            <x-table.cell><x-form.inputs.checkbox wire:model="selectedStableIds" value="{{ $stable->id }}" /></x-table.cell>
                            <x-table.cell><a class="mb-1 text-gray-800 text-hover-primary" href="{{ route('stables.show', $stable) }}">{{ $stable->name }}</a></x-table.cell>
                            <x-table.cell><div class="badge badge-{{ $stable->status->color() }}">{{ $stable->status->label() }}</div></x-table.cell>
                            <x-table.cell>{{ $stable->activatedAt?->toDateString() ?? 'No Start Date Set' }}</x-table.cell>
                            <x-table.cell class="text-end">
                                @include('livewire.stables.partials.action-cell')
                            </x-table.cell>
                        </x-table.row>
                    @empty
                        <x-table.row-no-data colspan="4"/>
                    @endforelse
                </x-table.body>
                <x-table.foot/>
            </x-table>
            <x-table.footer :collection="$stables" />
        </x-table.wrapper>
    </x-card.body>
</x-card>
