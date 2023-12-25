<x-datatable>
    <x-slot name="head">
        <x-table.heading sortable multi-column wire:click="sortBy('name')" :direction="$sorts['name'] ?? null" class="min-w-125px sorting">Event Name</x-table.heading>
        <x-table.heading sortable multi-column wire:click="sortBy('venue')" :direction="$sorts['date'] ?? null" class="min-w-125px sorting">Date</x-table.heading>
    </x-slot>
    <x-slot name="body">
        @forelse ($events as $event)
            <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:loading.class.delay="opacity-50" wire:key="row-{{ $event->id }}">
                <x-table.cell>
                    <a class="text-gray-800 text-hover-primary" href="{{ route('events.show', $event) }}">{{ $event->name }}</a>
                </x-table.cell>

                <x-table.cell>
                    {{ $event->date?->toDateString() ?? 'No Date Set'}}
                </x-table.cell>

            </x-table.row>
        @empty
            <x-table.row>
                <x-table.cell colspan="6">
                    <div class="flex items-center justify-center space-x-2">
                        <span class="py-8 text-xl font-medium text-cool-gray-400">No events found...</span>
                    </div>
                </x-table.cell>
            </x-table.row>
        @endforelse
    </x-slot>
    <x-slot name="footer">
        <x-table.footer :collection="$events" />
    </x-slot>
</x-datatable>
