<x-datatable :collection="$retiredTitles">
    <thead>
        <th>Id</th>
        <th>Title Name</th>
        <th>Date Retired</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($retiredTitles as $title)
            <tr>
                <td>{{ $title->id }}</td>
                <td>{{ $title->name }}</td>
                <td>{{ $title->retired_at->toDateString() }}</td>
                <td>
                    {{-- @include('titles.partials.action-cell', [
                        'title' => $title,
                        'actions' => collect([
                            'unretire'
                        ])
                    ]) --}}
                    <x-actions-dropdown>
                        <x-buttons.view :route="route('titles.show', $title)" />
                        <x-buttons.edit :route="route('titles.edit', $title)" />
                        <x-buttons.delete :route="route('titles.destroy', $title)" />
                        <x-buttons.unretire :route="route('titles.unretire', $title)" />
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
