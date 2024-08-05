@aware(['stable'])

<x-card class="card-flush mb-6 mb-xl-9">
    <x-card.header class="mt-6">
        <x-card.title>
            <h2>Previous Wrestlers(s)</h2>
        </x-card.title>
    </x-card.header>

    <x-card.body class="pt-0">
        <x-table.wrapper>
            <x-table>
                <x-table.head>
                    <x-table.heading sortable multi-column wire:click="sortBy('name')" :direction="$sorts['name'] ?? null" class="min-w-125px sorting">Wrestler Name</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">Date Joined</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">Date Left</x-table.heading>
                </x-table.head>
                <x-table.body>
                    @forelse ($previousWrestlers as $previousWrestler)
                        <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:key="row-{{ $previousWrestler->id }}">
                            <x-table.cell><x-route-link :route="route('wrestlers.show', $previousWrestler)" label="{{ $previousWrestler->name }}"/></x-table.cell>
                            <x-table.cell>{{ $previousWrestler->pivot->joined_at->toDateString() }}</x-table.cell>
                            <x-table.cell>{{ $previousWrestler->pivot->left_at->toDateString() }}</x-table.cell>
                        </x-table.row>
                    @empty
                        <x-table.row-no-data colspan="3"/>
                    @endforelse
                </x-slot>
            </x-table>
            <x-table.footer :collection="$previousWrestlers" />
        </x-table.wrapper>
    </x-card.body>
</x-card>
