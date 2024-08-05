<x-card>
    <x-card.header class="pt-6">
        <x-card.title>
            <x-search resource="Titles" />
        </x-card.title>

        <x-card.toolbar>
            <x-card.toolbar.actions>
                <x-buttons.create route="{{ route('titles.create') }}" resource="Title" />
            </x-card.toolbar.actions>
        </x-card.toolbar>
    </x-card.header>

    <x-card.body class="pt-0">
        <x-table.wrapper>
            <x-table>
                <x-table.head>
                    <x-table.heading class="w-10px pe-2 sorting_disabled"><x-form.inputs.checkbox wire:model="selectPage"/></x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('name')" :direction="$sorts['name'] ?? null" class="min-w-125px sorting">Title Name</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('status')" :direction="$sorts['status'] ?? null" class="min-w-125px sorting">Status</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">Current Champion</x-table.heading>
                    <x-table.heading class="text-end min-w-70px sorting_disabled">Actions</x-table.heading>
                </x-table.head>
                <x-table.body>
                    @forelse ($titles as $title)
                        <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:loading.class.delay="opacity-50" wire:key="row-{{ $title->id }}">
                            <x-table.cell><x-form.inputs.checkbox wire:model="selected" value="{{ $title->id }}" /></x-table.cell>
                            <x-table.cell><a class="text-gray-800 text-hover-primary" href="{{ route('titles.show', $title) }}">{{ $title->name }}</a></x-table.cell>
                            <x-table.cell><div class="badge badge-{{ $title->status->color() }}">{{ $title->status->label() }}</div></x-table.cell>
                            <x-table.cell>{{ $title->currentChampionship->champion->display_name ?? 'Vacant' }}</x-table.cell>
                            <x-table.cell class="text-end">
                                @include('livewire.titles.partials.action-cell')
                            </x-table.cell>
                        </x-table.row>
                    @empty
                        <x-table.row-no-data colspan="5"/>
                    @endforelse
                </x-table.body>
                <x-table.foot/>
            </x-table>
            <x-table.footer :collection="$titles"/>
        </x-table.wrapper>
    </x-card.body>
</x-card>
