@aware(['wrestler'])

<x-card class="card-flush mb-6 mb-xl-9">
    <x-card.header class="mt-6">
        <x-card.title>
            <h2>Previous Title Championships</h2>
        </x-card.title>
    </x-card.header>

    <x-card.body class="pt-0">
        <x-table.wrapper>
            <x-table>
                <x-table.head>
                    <x-table.heading sortable multi-column wire:click="sortBy('name')" :direction="$sorts['name'] ?? null" class="min-w-125px sorting">Title Name</x-table.heading>
                    <x-table.heading class="min-w-125px sorting_disabled">Previous Champion</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('days_held')" :direction="$sorts['days_held'] ?? null" class="min-w-70px sorting">Days Held</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('won_at')" :direction="$sorts['won_at'] ?? null" class="min-w-70px sorting">Dates Held</x-table.heading>
                </x-table.head>
                <x-table.body>
                    @forelse ($previousTitleChampionships as $previousTitleChampionship)
                        <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:key="row-{{ $previousTitleChampionship->title_id }}">
                            <x-table.cell><x-route-link :route="route('titles.show', $previousTitleChampionship->title)" label="{{ $previousTitleChampionship->title->name }}"/></x-table.cell>
                            <x-table.cell>
                                @if ($previousTitleChampionship->title->championships->first()->currentChampion->is($this->wrestler))
                                    {{  "First Champion" }}
                                @else
                                    <x-route-link :route="route('wrestlers.show', $previousTitleChampionship->title->championships->first()->currentChampion)" label="{{ $previousTitleChampionship->title->championships->first()->currentChampion->name }}"/>
                                @endif
                            </x-table.cell>
                            <x-table.cell>{{ $previousTitleChampionship->days_held_count }}</x-table.cell>
                            <x-table.cell>
                                {{ $previousTitleChampionship->won_at?->toDateString() ?? "no won at" }}
                                    -
                                {{ $previousTitleChampionship->lost_at?->toDateString() ?? "Present" }}
                            </x-table.cell>
                        </x-table.row>
                    @empty
                        <x-table.row-no-data colspan="4"/>
                    @endforelse
                </x-table.body>
                <x-table.foot/>
            </x-table>
            <x-table.footer :collection="$previousTitleChampionships" />
        </x-table.wrapper>
    </x-card.body>
</x-card>
