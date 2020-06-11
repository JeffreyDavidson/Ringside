<x-datatable :collection="$venues">
    <thead>
        <th>Id</th>
        <th>Venue Name</th>
        <th>Address</th>
        <th>City</th>
        <th>State</th>
        <th>Zip Code</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($venues as $venue)
            <tr>
                <td>{{ $venue->id }}</td>
                <td>{{ $venue->name }}</td>
                <td>{{ $venue->address1 }}</td>
                <td>{{ $venue->city }}</td>
                <td>{{ $venue->state }}</td>
                <td>{{ $venue->zip }}</td>
                <td>
                    @include('venues.partials.action-cell', [
                        'venue' => $venue,
                        'actions' => collect([

                        ])
                    ])
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">No matching records found</td>
            </tr>
        @endforelse
    </tbody>
</x-datatable>

