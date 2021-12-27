<x-data-table :collection="$injuredReferees">
    <thead>
        <th>Id</th>
        <th>Referee Name</th>
        <th>Date Injured</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse ($injuredReferees as $referee)
            <tr>
                <td>{{ $referee->id }}</td>
                <td>{{ $referee->full_name }}</td>
                <td>{{ $referee->current_injured_at->toDateString() }}</td>
                <td>
                    <x-actions-dropdown>
                        <x-buttons.view :route="route('referees.show', $referee)" />
                        <x-buttons.edit :route="route('referees.edit', $referee)" />
                        <x-buttons.delete :route="route('referees.destroy', $referee)" />
                        <x-buttons.retire :route="route('referees.retire', $referee)" />
                        <x-buttons.release :route="route('referees.release', $referee)" />
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
