<x-datatable :collection="$retiredWrestlers">
    <thead>
        <th>Id</th>
        <th>Wrestler Name</th>
        <th>Hometown</th>
        <th>Date Retired</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($retiredWrestlers as $wrestler)
            <tr>
                <td>{{ $wrestler->id }}</td>
                <td>{{ $wrestler->name }}</td>
                <td>{{ $wrestler->hometown }}</td>
                <td>{{ $wrestler->retired_at->toDateString() }}</td>
                <td>
                    @include('wrestlers.partials.action-cell', [
                        'wrestler' => $wrestler,
                        'actions' => collect([
                            'unretire'
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
