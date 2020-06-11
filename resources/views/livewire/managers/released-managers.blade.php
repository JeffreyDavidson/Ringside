<x-datatable :collection="$releasedManagers">
    <thead>
        <th>Id</th>
        <th>Manager Name</th>
        <th>Date Released</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($releasedManagers as $manager)
            <tr>
                <td>{{ $manager->id }}</td>
                <td>{{ $manager->full_name }}</td>
                <td>{{ $manager->released_at->toDateString() }}</td>
                <td>
                    @include('managers.partials.action-cell', [
                        'manager' => $manager,
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
