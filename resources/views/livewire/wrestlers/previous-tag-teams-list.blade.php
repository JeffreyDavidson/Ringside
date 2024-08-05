@aware(['tagTeam'])

<x-card class="card-flush mb-6 mb-xl-9">
    <x-card.header class="mt-6">
        <x-card.title>
            <h2>Previous Tag Teams</h2>
        </x-card.title>
    </x-card.header>

    <x-card.body class="pt-0">
        <x-table.wrapper>
            <x-table>
                <x-table.head>
                    <x-table.heading sortable multi-column wire:click="sortBy('name')" :direction="$sorts['name'] ?? null" class="min-w-125px sorting">Tag Team Name</x-table.heading>
                    <x-table.heading class="min-w-125px sorting_disabled">Tag Team Partner</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">Date Joined</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">Date Left</x-table.heading>
                </x-table.head>
                <x-table.body>
                    @forelse ($previousTagTeams as $previousTagTeam)
                        <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:key="row-{{ $previousTagTeam->id }}">
                            <x-table.cell><x-route-link :route="route('tag-teams.show', $previousTagTeam)" label="{{ $previousTagTeam->name }}"/></x-table.cell>
                            <x-table.cell><x-route-link :route="route('wrestlers.show', $previousTagTeam->wrestlers->except($this->wrestler->id)->first())" label="{{ $previousTagTeam->wrestlers->except($this->wrestler->id)->first()->name }}"/></x-table.cell>
                            <x-table.cell>{{ $previousTagTeam->pivot->joined_at->toDateString() }}</x-table.cell>
                            <x-table.cell>{{ $previousTagTeam->pivot->left_at->toDateString() }}</x-table.cell>
                        </x-table.row>
                    @empty
                        <x-table.row-no-data colspan="4"/>
                    @endforelse
                </x-table.body>
                <x-table-foot />
            </x-table>
            <x-table.footer :collection="$previousTagTeams" />
        </x-table.wrapper>
    </x-card.body>
</x-card>
