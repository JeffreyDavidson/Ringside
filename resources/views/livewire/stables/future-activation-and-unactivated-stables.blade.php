<x-data-table :collection="$futureActivationAndUnactivatedStables">
    <thead>
        <th>Id</th>
        <th>Stable Name</th>
        <th>Date Introduced</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse ($futureActivationAndUnactivatedStables as $stable)
            <tr>
                <td>{{ $stable->id }}</td>
                <td>{{ $stable->name }}</td>
                <td>
                    @isset($stable->first_activated_at)
                        {{ $stable->first_activated_at->toDateString() }}
                    @else
                        TBD
                    @endisset
                </td>
                <td>
                    <x-actions-dropdown>
                        <x-buttons.view :route="route('stables.show', $stable)" />
                        <x-buttons.edit :route="route('stables.edit', $stable)" />
                        <x-buttons.delete :route="route('stables.destroy', $stable)" />
                        <x-buttons.activate :route="route('stables.activate', $stable)" />
                    </x-actions-dropdown>
                </td>
            </tr>
        @empty
            <tr><td colspan="4">No matching records found</td></tr>
        @endforelse
    </tbody>
</x-datatable>
