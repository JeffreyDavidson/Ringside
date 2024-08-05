<x-card>
    <x-card.header class="pt-6">
        <x-card.title>
            <x-search resource="Events" />
        </x-card.title>

        <x-card.toolbar>
            <x-card.toolbar.actions>
                <x-buttons.create route="{{ route('events.create') }}" resource="Event" />
            </x-card.toolbar.actions>
        </x-card.toolbar>
    </x-card.header>

    <x-card.body class="pt-0">
        <x-table.wrapper>
            <x-table>
                <x-table.head>
                    <x-table.heading class="w-10px pe-2 sorting_disabled"><x-form.inputs.checkbox wire:model="selectPage" /></x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('name')" :direction="$sorts['name'] ?? null" class="min-w-125px sorting">Event Name</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('status')" :direction="$sorts['status'] ?? null" class="min-w-125px sorting">Status</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('venue')" :direction="$sorts['date'] ?? null" class="min-w-125px sorting">Date</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('venue')" :direction="$sorts['venue'] ?? null" class="min-w-125px sorting">Venue</x-table.heading>
                    <x-table.heading class="text-end min-w-70px sorting_disabled">Actions</x-table.heading>
                </x-table.head>
                <x-table.body>
                    @forelse ($events as $event)
                        <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:loading.class.delay="opacity-50" wire:key="row-{{ $event->id }}">
                            <x-table.cell><x-form.inputs.checkbox wire:model="selected" value="{{ $event->id }}" /></x-table.cell>
                            <x-table.cell><a class="text-gray-800 text-hover-primary" href="{{ route('events.show', $event) }}">{{ $event->name }}</a></x-table.cell>
                            <x-table.cell><div class="badge badge-{{ $event->status->color() }}">{{ $event->status->label() }}</div></x-table.cell>
                            <x-table.cell>{{ $event->date?->format('F m, Y') }}</x-table.cell>
                            <x-table.cell>{{ $event->venue->name ?? "" }}</x-table.cell>
                            <x-table.cell class="text-end">
                                @include('livewire.events.partials.action-cell')
                            </x-table.cell>
                        </x-table.row>
                    @empty
                        <x-table.row-no-data colspan="6"/>
                    @endforelse
                </x-table.body>
                <x-table.foot/>
            </x-table>
            <x-table.footer :collection="$events"/>
        </x-table.wrapper>
    </x-card.body>
</x-card>
