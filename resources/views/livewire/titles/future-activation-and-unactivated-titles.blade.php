<x-data-table :collection="$futureActivationAndUnactivatedTitles">
    <thead>
        <th>Id</th>
        <th>Title Name</th>
        <th>Date Introduced</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse ($futureActivationAndUnactivatedTitles as $title)
            <tr>
                <td>{{ $title->id }}</td>
                <td>{{ $title->name }}</td>
                <td>
                    @isset($title->first_activated_at)
                        {{ $title->first_activated_at->toDateString() }}
                    @else
                        TBD
                    @endisset
                </td>
                <td>
                    <x-actions-dropdown>
                        <x-buttons.view :route="route('titles.show', $title)" />
                        <x-buttons.edit :route="route('titles.edit', $title)" />
                        <x-buttons.delete :route="route('titles.destroy', $title)" />
                        <x-buttons.activate :route="route('titles.activate', $title)" />
                    </x-actions-dropdown>
                </td>
            </tr>
        @empty
            <tr><td colspan="4">No matching records found</td></tr>
        @endforelse
    </tbody>
</x-datatable>
