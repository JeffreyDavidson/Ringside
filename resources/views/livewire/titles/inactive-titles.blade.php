<x-datatable :collection="$inactiveTitles">
    <thead>
        <th>Id</th>
        <th>Title Name</th>
        <th>Date Deactivated</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($inactiveTitles as $title)
            <tr>
                <td>{{ $title->id }}</td>
                <td>{{ $title->name }}</td>
                <td>{{ $title->deactivated_at->toDateString() }}</td>
                <td>
                    {{-- @include('titles.partials.action-cell', [
                        'title' => $title,
                        'actions' => collect([
                            'activate'
                        ])
                    ]) --}}
                    <x-actions-dropdown>
                        <x-buttons.view :route="route('titles.show', $title)" />
                        <x-buttons.edit :route="route('titles.edit', $title)" />
                        <x-buttons.delete :route="route('titles.destroy', $title)" />
                        <x-buttons.activate :route="route('titles.activate', $title)" />
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