<div class="pt-3 mb-5 card card-flush mb-xl-10">
    @include('livewire.events.partials.header')

    <div class="py-4 card-body">
        <x-data-table :collection="$unscheduledEvents">
            <x-table.row-heading>
                <x-table.checkbox-heading />
                <th>Event Name</th>
                <th>Status</th>
                <x-table.actions-heading />
            </x-table.row-heading>
            <x-table.body>
                @forelse ($unscheduledEvents as $event)
                    <tr>
                        <x-table.cell-checkbox value="{{ $event->id }}" />
                        <x-table.cell-link link="{{ route('events.show', $event) }}" text="{{ $event->name }}" />
                        <td><div class="badge badge-light-warning">{{ $event->status->label }}</div></td>
                        <x-table.actions-cell>
                            @include('events.partials.action-cell', [
                                'event' => $event,
                            ])
                        </x-table.actions-cell>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No matching records found</td>
                    </tr>
                @endforelse
            </x-table.body>
        </x-datatable>
    </div>
</div>
