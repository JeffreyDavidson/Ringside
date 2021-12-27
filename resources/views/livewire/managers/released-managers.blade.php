<x-data-table :collection="$releasedManagers">
    <thead>
        <th>Id</th>
        <th>Manager Name</th>
        <th>Date Released</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse ($releasedManagers as $manager)
            <tr>
                <td>{{ $manager->id }}</td>
                <td>{{ $manager->full_name }}</td>
                <td>{{ $manager->released_at->toDateString() }}</td>
                <td>
                    <x-actions-dropdown>
                        <x-buttons.view :route="route('managers.show', $manager)" />
                        <x-buttons.edit :route="route('managers.edit', $manager)" />
                        <x-buttons.delete :route="route('managers.destroy', $manager)" />
                        <x-buttons.employ :route="route('managers.employ', $manager)" />
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
