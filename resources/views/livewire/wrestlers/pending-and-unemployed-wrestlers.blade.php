<x-datatable :collection="$pendingAndUnemployedWrestlers">
    <thead>
        <th>Id</th>
        <th>Wrestler Name</th>
        <th>Hometown</th>
        <th>Date Employed</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse($pendingAndUnemployedWrestlers as $wrestler)
            <tr>
                <td>{{ $wrestler->id }}</td>
                <td>{{ $wrestler->name }}</td>
                <td>{{ $wrestler->hometown }}</td>
                <td>
                    @if($wrestler->hasFutureEmployment())
                        {{ $wrestler->employed_at->toDateString() }}
                    @else
                        TBD
                    @endif
                </td>
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
                        <x-buttons.employ :route="route('wrestlers.employ', $wrestler)" />
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

