<x-datatable>
    <x-slot name="head">
        <x-table.heading class="min-w-125px sorting_disabled">Champion</x-table.heading>
        <x-table.heading class="min-w-70px sorting_disabled">Defeated</x-table.heading>
        <x-table.heading class="min-w-70px sorting_disabled">Dates Held</x-table.heading>
        <x-table.heading class="min-w-70px sorting_disabled">Reign Length</x-table.heading>
    </x-slot>
    <x-slot name="body">
        @forelse ($titleChampionships as $titleChampionship)
            <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:loading.class.delay="opacity-50" wire:key="row-{{ $titleChampionship->id }}">
                <x-table.cell>
                    <a class="mb-1 text-gray-800 text-hover-primary" href="{{ route('wrestlers.show', $titleChampionship->currentChampion) }}">{{ $titleChampionship->currentChampion->name }}</a>
                </x-table.cell>

                <x-table.cell>
                    <a class="mb-1 text-gray-800 text-hover-primary" href="{{ route('wrestlers.show', $titleChampionship->currentChampion) }}">{{ $titleChampionship->previousChampion->name }}</a>
                </x-table.cell>

                <x-table.cell>
                    @if ($titleChampionship->lost_at)
                        {{ $titleChampionship->won_at->toDateString() }} - {{ $titleChampionship->lost_at->toDateString() }}
                    @else
                        {{ $titleChampionship->won_at->toDateString() }} - PRESENT
                    @endif
                </x-table.cell>

                <x-table.cell>
                    {{ $titleChampionship->lengthInDays() }} days
                </x-table.cell>

            </x-table.row>
        @empty
            <x-table.row>
                <x-table.cell colspan="6">
                    <div class="flex items-center justify-center space-x-2">
                        <span class="py-8 text-xl font-medium text-cool-gray-400">No title championships found...</span>
                    </div>
                </x-table.cell>
            </x-table.row>
        @endforelse
    </x-slot>
    <x-slot name="footer">
        <x-table.footer :collection="$titleChampionships" />
    </x-slot>
</x-datatable>
