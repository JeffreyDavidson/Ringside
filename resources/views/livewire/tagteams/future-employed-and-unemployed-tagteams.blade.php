<x-data-table :collection="$futureEmployedAndUnemployedTagTeams">
    <thead>
        <th>Id</th>
        <th>Tag Team Name</th>
        <th>Date Employed</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($futureEmployedAndUnemployedTagTeams as $tagTeam)
            <tr>
                <td>{{ $tagTeam->id }}</td>
                <td>{{ $tagTeam->name }}</td>
                <td>
                    @isset($tagTeam->first_employed_at)
                        {{ $tagTeam->first_employed_at->toDateString() }}
                    @else
                        TBD
                    @endisset
                </td>
                <td>
                    <x-actions-dropdown>
                        <x-buttons.view :route="route('tag-teams.show', $tagTeam)" />
                        <x-buttons.edit :route="route('tag-teams.edit', $tagTeam)" />
                        <x-buttons.delete :route="route('tag-teams.destroy', $tagTeam)" />
                        <x-buttons.retire :route="route('tag-teams.retire', $tagTeam)" />
                        <x-buttons.suspend :route="route('tag-teams.suspend', $tagTeam)" />
                        <x-buttons.reinstate :route="route('tag-teams.reinstate', $tagTeam)" />
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
