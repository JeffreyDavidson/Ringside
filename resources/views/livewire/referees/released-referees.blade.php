<x-datatable :collection="$releasedReferees">
    <thead>
        <th>Id</th>
        <th>Referee Name</th>
        <th>Date Released</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($releasedReferees as $referee)
            <tr>
                <td>{{ $referee->id }}</td>
                <td>{{ $referee->full_name }}</td>
                <td>{{ $referee->released_at->toDateString() }}</td>
                <td>
                    @include('referees.partials.action-cell', [
                        'referee' => $referee,
                        'actions' => collect([
                            'employ'
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
