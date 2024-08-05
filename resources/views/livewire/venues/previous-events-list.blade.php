<x-card>
    <x-card.header>
        <x-card.title>
            <h2>Previous Event(s)</h2>
        </x-card.title>
    </x-card.header>

    <x-card.body class="pt-0">
        <x-table.wrapper>
            <x-table>
                <x-table.head>
                    <x-table.heading sortable multi-column wire:click="sortBy('name')" :direction="$sorts['name'] ?? null" class="min-w-125px sorting">Event Name</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('date')" :direction="$sorts['date'] ?? null" class="min-w-125px sorting">Date</x-table.heading>
                </x-table.head>
                <x-table.body>
                    @forelse ($previousEvents as $previousEvent)
                        <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:loading.class.delay="opacity-50" wire:key="row-{{ $previousEvent->id }}">
                            <x-table.cell><x-route-link :route="route('events.show', $previousEvent)" label="{{ $previousEvent->name }}"/></x-table.cell>
                            <x-table.cell>{{ $previousEvent->date?->toDateString() ?? 'No Date Set'}}</x-table.cell>
                        </x-table.row>
                    @empty
                        <x-table.row-no-data colspan="2"/>
                    @endforelse
                </x-table.body>
                <x-table.foot/>
            </x-table>
            <x-table.footer :collection="$previousEvents"/>
        </x-table.wrapper>
    </x-card.body>
</x-card>
