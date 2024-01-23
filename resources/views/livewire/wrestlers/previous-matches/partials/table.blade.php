<x-datatable>
    <x-slot name="head">
        <x-table.heading
            sortable
            multi-column
            wire:click="sortBy('name')"
            :direction="$sorts['name'] ?? null"
            class="min-w-125px sorting">Event Name</x-table.heading>
        <x-table.heading
            sortable
            multi-column
            wire:click="sortBy('date')"
            :direction="$sorts['date'] ?? null"
            class="min-w-70px sorting">Date</x-table.heading>
        <x-table.heading class="min-w-70px sorting_disabled">Opponent(s)</x-table.heading>
        <x-table.heading class="min-w-70px sorting_disabled">Title Match?</x-table.heading>
        <x-table.heading class="min-w-70px sorting_disabled">Result</x-table.heading>
    </x-slot>
    <x-slot name="body">
        @forelse ($previousMatches as $previousMatch)
            <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:key="row-{{ $previousMatch->id }}">
                <x-table.cell>
                    <x-route-link
                        :route="route('events.show', $previousMatch->event)"
                        label="{{ $previousMatch->event->name }}"
                    />
                </x-table.cell>

                <x-table.cell>
                    {{ $previousMatch->event->date->toDateString() }}
                </x-table.cell>

                <x-table.cell>
                    @foreach ($previousMatch->competitors->except($this->wrestler->id) as $opponent)
                        <x-route-link
                            :route="route(str($opponent->getTable())->replace('_', '-').'.show', $opponent)"
                            label="{{ $opponent->name }}"
                        />
                    @endforeach
                </x-table.cell>

                <x-table.cell>
                    @if ($previousMatch->titles->isNotEmpty())
                        @foreach($previousMatch->titles as $title)
                            <x-route-link
                                :route="route('titles.show', $title)"
                                label="{{ $title->name }}" />
                        @endforeach
                    @else
                        {{ "N/A" }}
                    @endif
                </x-table.cell>

                <x-table.cell>
                    {{ $previousMatch->result->winner->is($this->wrestler) ? "Won" : "Lost" }}
                    by {{ $previousMatch->result->decision->name }}
                </x-table.cell>

            </x-table.row>
        @empty
            <x-table.row-no-data colspan="5"/>
        @endforelse
    </x-slot>
    <x-slot name="footer">
        <x-table.footer :collection="$previousMatches" />
    </x-slot>
</x-datatable>
