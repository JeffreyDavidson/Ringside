<x-datatable :collection="$employedReferees">
    <thead>
        <th>Id</th>
        <th>Referee Name</th>
        <th>Date Employed</th>
        <th>Status</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($employedReferees as $referee)
            <tr>
                <td>{{ $referee->id }}</td>
                <td>{{ $referee->full_name }}</td>
                <td>{{ $referee->employed_at->toDateString() }}</td>
                <td>{{ $referee->status->label() }}</td>
                <td>
                    @include('referees.partials.action-cell', [
                        'referee' => $referee,
                        'actions' => collect([
                            'retire', 'employ', 'release', 'suspend', 'reinstate', 'injure', 'clearInjury'
                        ])
                    ])
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">No matching records found</td>
            </tr>
        @endforelse
    </tbody>
</x-datatable>

