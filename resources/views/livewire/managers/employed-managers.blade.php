<x-datatable :collection="$employedManagers">
    <thead>
        <th>Id</th>
        <th>Manager Name</th>
        <th>Date Employed</th>
        <th>Status</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($employedManagers as $manager)
            <tr>
                <td>{{ $manager->id }}</td>
                <td>{{ $manager->full_name }}</td>
                <td>{{ $manager->employed_at->toDateString() }}</td>
                <td>{{ $manager->status->label() }}</td>
                <td>
                    @include('managers.partials.action-cell', [
                        'manager' => $manager,
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

