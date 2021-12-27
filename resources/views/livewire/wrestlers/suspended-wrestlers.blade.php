<x-data-table :collection="$suspendedWrestlers">
    <thead>
        <th>Id</th>
        <th>Wrestler Name</th>
        <th>Hometown</th>
        <th>Date Suspended</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse ($suspendedWrestlers as $wrestler)
            <tr>
                <td>{{ $wrestler->id }}</td>
                <td>{{ $wrestler->name }}</td>
                <td>{{ $wrestler->hometown }}</td>
                <td>{{ $wrestler->current_suspended_at->toDateString() }}</td>
                <td>
                    <x-actions-dropdown>
                        <x-buttons.view :route="route('wrestlers.show', $wrestler)" />
                        <x-buttons.edit :route="route('wrestlers.edit', $wrestler)" />
                        <x-buttons.delete :route="route('wrestlers.destroy', $wrestler)" />
                        <x-buttons.reinstate :route="route('wrestlers.reinstate', $wrestler)" />
                        <x-buttons.retire :route="route('wrestlers.retire', $wrestler)" />
                        <x-buttons.release :route="route('wrestlers.release', $wrestler)" />
                    </x-actions-dropdown>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">No matching records found</td>
            </tr>
        @endforelse
    </tbody>
</x-datatable>
