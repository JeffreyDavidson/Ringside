<x-datatable :collection="$retiredManagers">
    <thead>
        <th>Id</th>
        <th>Manager Name</th>
        <th>Date Retired</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($retiredManagers as $manager)
            <tr>
                <td>{{ $manager->id }}</td>
                <td>{{ $manager->full_name }}</td>
                <td>{{ $manager->retired_at->toDateString() }}</td>
                <td>
                    @include('managers.partials.action-cell', [
                        'manager' => $manager,
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
