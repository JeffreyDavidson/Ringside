<x-data-table :collection="$retiredManagers">
    <thead>
        <th>Id</th>
        <th>Manager Name</th>
        <th>Date Retired</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse ($retiredManagers as $manager)
            <tr>
                <td>{{ $manager->id }}</td>
                <td>{{ $manager->full_name }}</td>
                <td>{{ $manager->current_retired_at->toDateString() }}</td>
                <td>
                    <x-actions-dropdown>
                        <x-buttons.view :route="route('managers.show', $manager)" />
                        <x-buttons.edit :route="route('managers.edit', $manager)" />
                        <x-buttons.delete :route="route('managers.destroy', $manager)" />
                        <x-buttons.unretire :route="route('managers.unretire', $manager)" />
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
