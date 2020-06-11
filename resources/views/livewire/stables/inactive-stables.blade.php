<x-datatable :collection="$inactiveStables">
    <thead>
        <th>Id</th>
        <th>Stable Name</th>
        <th>Date Deactivated</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($inactiveStables as $stable)
            <tr>
                <td>{{ $stable->id }}</td>
                <td>{{ $stable->name }}</td>
                <td>{{ $stable->deactivated_at->toDateString() }}</td>
                <td>
                    @include('stables.partials.action-cell', [
                        'stable' => $stable,
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
