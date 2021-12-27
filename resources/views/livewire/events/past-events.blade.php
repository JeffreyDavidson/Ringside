<x-data-table :collection="$pastEvents">
    <thead>
        <th>Id</th>
        <th>Event Name</th>
        <th>Date</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse ($pastEvents as $event)
            <tr>
                <td>{{ $event->id }}</td>
                <td>{{ $event->name }}</td>
                <td>{{ $event->date->toDateString() }}</td>
                <td>
                    @include('events.partials.action-cell', [
                        'event' => $event,
                        'actions' => collect([
                        ])
                    ])
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4">No matching records found</td>
            </tr>
        @endforelse
    </tbody>
</x-datatable>
