<x-datatable :collection="$futureEmployedAndUnemployedReferees">
    <thead>
        <th>Id</th>
        <th>Referee Name</th>
        <th>Date Employed</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($futureEmployedAndUnemployedReferees as $referee)
            <tr>
                <td>{{ $referee->id }}</td>
                <td>{{ $referee->full_name }}</td>
                <td>
                    @isset($title->first_employed_at)
                        {{ $title->first_employed_at->toDateString() }}
                    @else
                        TBD
                    @endisset
                </td>
                <td>
                    <x-actions-dropdown>
                        <x-buttons.view :route="route('referees.show', $referee)" />
                        <x-buttons.edit :route="route('referees.edit', $referee)" />
                        <x-buttons.delete :route="route('referees.destroy', $referee)" />
                        <x-buttons.employ :route="route('referees.employ', $referee)" />
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
