<x-data-table :collection="$suspendedTagTeams">
    <thead>
        <th>Id</th>
        <th>Tag Team Name</th>
        <th>Date Suspended</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse ($suspendedTagTeams as $tagTeam)
            <tr>
                <td>{{ $tagTeam->id }}</td>
                <td>{{ $tagTeam->full_name }}</td>
                <td>{{ $tagTeam->current_suspended_at->toDateString() }}</td>
                <td>
                    <x-actions-dropdown>
                        <x-buttons.view :route="route('tagTeams.show', $tagTeam)" />
                        <x-buttons.edit :route="route('tagTeams.edit', $tagTeam)" />
                        <x-buttons.delete :route="route('tagTeams.destroy', $tagTeam)" />
                        <x-buttons.reinstate :route="route('tagTeams.reinstate', $tagTeam)" />
                        <x-buttons.retire :route="route('tagTeams.retire', $tagTeam)" />
                        <x-buttons.release :route="route('tagTeams.release', $tagTeam)" />
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
