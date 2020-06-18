<x-datatable :collection="$injuredManagers">
    <thead>
        <th>Id</th>
        <th>Manager Name</th>
        <th>Date Injured</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($injuredManagers as $manager)
            <tr>
                <td>{{ $manager->id }}</td>
                <td>{{ $manager->full_name }}</td>
                <td>{{ $manager->current_injured_at->toDateString() }}</td>
                <td>
                    <x-actions-dropdown>
                        <x-buttons.view :route="route('managers.show', $manager)" />
                        <x-buttons.edit :route="route('managers.edit', $manager)" />
                        <x-buttons.delete :route="route('managers.destroy', $manager)" />
                        <x-buttons.retire :route="route('managers.retire', $manager)" />
                        <x-buttons.release :route="route('managers.release', $manager)" />
                    </x-actions-dropdown>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4">No matching records found</td>
            </tr>
        @endforelse
    </tbody>
</x-datatable>

