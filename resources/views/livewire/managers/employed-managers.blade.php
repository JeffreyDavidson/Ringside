<x-data-table :collection="$employedManagers">
    <thead>
        <th>Id</th>
        <th>Manager Name</th>
        <th>Date Employed</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse ($employedManagers as $manager)
            <tr>
                <td>{{ $manager->id }}</td>
                <td>{{ $manager->full_name }}</td>
                <td>{{ $manager->first_employed_at->toDateString() }}</td>
                <td>
                    <x-actions-dropdown>
                        <x-buttons.view :route="route('managers.show', $manager)" />
                        <x-buttons.edit :route="route('managers.edit', $manager)" />
                        <x-buttons.delete :route="route('managers.destroy', $manager)" />
                        <x-buttons.suspend :route="route('managers.suspend', $manager)" />
                        <x-buttons.injure :route="route('managers.injure', $manager)" />
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
