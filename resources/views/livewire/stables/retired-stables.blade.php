<x-data-table :collection="$retiredStables">
    <thead>
        <th>Id</th>
        <th>Stable Name</th>
        <th>Date Retired</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse ($retiredStables as $stable)
            <tr>
                <td>{{ $stable->id }}</td>
                <td>{{ $stable->name }}</td>
                <td>{{ $stable->retired_at->toDateString() }}</td>
                <td>
                    @include('stables.partials.action-cell', [
                        'stable' => $stable,
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
