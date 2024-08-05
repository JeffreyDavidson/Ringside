<x-card>
    <x-card.header>
        <x-card.title>
            <h2>Title Championships</h2>
        </x-card.title>
    </x-card.header>

    <x-card.body class="pt-0">
        <x-table.wrapper>
            <x-table>
                <x-table.head>
                    <x-table.heading class="min-w-125px sorting_disabled">Champion</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">Defeated</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">Dates Held</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">Reign Length</x-table.heading>
                </x-table.head>
                <x-table.body>
                    @forelse ($titleChampionships as $titleChampionship)
                        <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:loading.class.delay="opacity-50" wire:key="row-{{ $titleChampionship->id }}">
                            <x-table.cell><a class="mb-1 text-gray-800 text-hover-primary" href="{{ route('wrestlers.show', $titleChampionship->currentChampion) }}">{{ $titleChampionship->currentChampion->name }}</a></x-table.cell>
                            <x-table.cell><a class="mb-1 text-gray-800 text-hover-primary" href="{{ route('wrestlers.show', $titleChampionship->currentChampion) }}">{{ $titleChampionship->previousChampion->name }}</a></x-table.cell>
                            <x-table.cell>
                                @if ($titleChampionship->lost_at)
                                    {{ $titleChampionship->won_at->toDateString() }} - {{ $titleChampionship->lost_at->toDateString() }}
                                @else
                                    {{ $titleChampionship->won_at->toDateString() }} - PRESENT
                                @endif
                            </x-table.cell>
                            <x-table.cell>{{ $titleChampionship->lengthInDays() }} days</x-table.cell>
                        </x-table.row>
                    @empty
                        <x-table.row-no-data colspan="4"/>
                    @endforelse
                </x-table.body>
                <x-table.foot/>
            </x-table>
            <x-table.footer :collection="$titleChampionships"/>
        </x-table.wrapper>
    </x-card.body>
</x-card>
