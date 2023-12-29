<x-datatable>
    <x-slot name="head">
        <x-table.heading class="min-w-125px sorting_disabled">Title Name</x-table.heading>
        <x-table.heading class="min-w-70px sorting_disabled">Times Held</x-table.heading>
        <x-table.heading class="min-w-70px sorting_disabled">Date Last Held</x-table.heading>
    </x-slot>
    <x-slot name="body">
        @forelse ($titlesChampionships as $titleChampionship)
            <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:key="row-{{ $titleChampionship->title_id }}">
                <x-table.cell>
                    <x-route-link
                        :route="route('titles.show', $titleChampionship->title)"
                        label="{{ $titleChampionship->title->name }}"
                    />
                </x-table.cell>
                <x-table.cell>{{ $titleChampionship->title_count }}</x-table.cell>
                <x-table.cell>
                    {{ $titleChampionship->won_at->toDateString() }}
                        -
                    {{ $titleChampionship->lost_at?->toDateString() ?? "Present" }}
                </x-table.cell>
            </x-table.row>
        @empty
            <x-table.row-no-data colspan="3"/>
        @endforelse
    </x-slot>
    <x-slot name="footer">
        <x-table.footer :collection="$titlesChampionships" />
    </x-slot>
</x-datatable>
