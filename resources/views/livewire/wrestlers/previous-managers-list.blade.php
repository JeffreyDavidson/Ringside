@aware(['wrestler'])

<x-card class="card-flush mb-6 mb-xl-9">
    <x-card.header class="mt-6">
        <x-card.title>
            <h2>Previous Managers</h2>
        </x-card.title>
    </x-card.header>

    <x-card.body class="pt-0">
        <x-table.wrapper>
            <x-table>
                <x-table.head>
                    <x-table.heading sortable multi-column wire:click="sortBy('full_name')" :direction="$sorts['full_name'] ?? null" class="min-w-125px sorting">Manager Name</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">Date Hired</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">Date Left</x-table.heading>
                </x-table.head>
                <x-table.body>
                    @forelse ($previousManagers as $previousManager)
                        <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:key="row-{{ $previousManager->id }}">
                            <x-table.cell><x-route-link :route="route('managers.show', $previousManager)" label="{{ $previousManager->full_name }}"/></x-table.cell>
                            <x-table.cell>{{ $previousManager->pivot->hired_at->toDateString() }}</x-table.cell>
                            <x-table.cell>{{ $previousManager->pivot->left_at->toDateString() }}</x-table.cell>
                        </x-table.row>
                    @empty
                        <x-table.row-no-data colspan="3"/>
                    @endforelse
                </x-table.body>
                <x-table-foot/>
            </x-table>
            <x-table.footer :collection="$previousMatches"/>
        </x-table.wrapper>
    </x-card.body>
</x-card>
