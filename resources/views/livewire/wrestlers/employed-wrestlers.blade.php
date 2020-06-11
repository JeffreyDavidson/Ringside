<x-datatable :collection="$employedWrestlers">
    <thead>
        <th>Id</th>
        <th>Wrestler Name</th>
        <th>Hometown</th>
        <th>Date Employed</th>
        <th>Status</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($employedWrestlers as $wrestler)
            <tr>
                <td>{{ $wrestler->id }}</td>
                <td>{{ $wrestler->name }}</td>
                <td>{{ $wrestler->hometown }}</td>
                <td>{{ $wrestler->employed_at->toDateString() }}</td>
                <td>{{ $wrestler->status->label() }}</td>
                <td>
                    {{-- @include('wrestlers.partials.action-cell', [
                        'wrestler' => $wrestler,
                        'actions' => collect([
                            'retire', 'employ', 'release', 'suspend', 'reinstate', 'injure', 'clearInjury'
                        ])
                    ]) --}}
                    <x-actions-dropdown>
                        <x-buttons.view :route="route('wrestlers.show', $wrestler)" />
                        <x-buttons.edit :route="route('wrestlers.edit', $wrestler)" />
                        <x-buttons.delete :route="route('wrestlers.destroy', $wrestler)" />
                        <x-buttons.retire :route="route('wrestlers.retire', $wrestler)" />
                        <x-buttons.release :route="route('wrestlers.release', $wrestler)" />
                    </x-actions-dropdown>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">No matching records found</td>
            </tr>
        @endforelse
    </tbody>
</x-datatable>

