<x-datatable :collection="$pendingAndUnemployedTagTeams">
    <thead>
        <th>Id</th>
        <th>Tag Team Name</th>
        <th>Date Employed</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($pendingAndUnemployedTagTeams as $tagTeam)
            <tr>
                <td>{{ $tagTeam->id }}</td>
                <td>{{ $tagTeam->name }}</td>
                <td>{{ $tagTeam->employed_at->toDateString() }}</td>
                <td>
                    @if($tagTeam->hasFutureEmployment())
                        @if($tagTeam->employed_at)
                        {{ $tagTeam->employed_at->toDateString() }}
                        @endif
                    @else
                        TBD
                    @endif
                </td>
                <td>
                    <!-- @include('tagTeams.partials.action-cell', [
                        'tagTeam' => $tagTeam,
                        'actions' => collect([
                            'retire', 'release', 'suspend', 'reinstate',
                        ])
                    ]) -->
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
