<x-datatable :collection="$retiredReferees">
    <thead>
        <th>Id</th>
        <th>Referee Name</th>
        <th>Date Retired</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($retiredReferees as $referee)
            <tr>
                <td>{{ $referee->id }}</td>
                <td>{{ $referee->full_name }}</td>
                <td>{{ $referee->retired_at->toDateString() }}</td>
                <td>
                    @include('referees.partials.action-cell', [
                        'referee' => $referee,
                        'actions' => collect([
                            'unretire'
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
