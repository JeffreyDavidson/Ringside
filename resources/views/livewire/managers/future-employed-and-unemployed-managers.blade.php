<x-data-table :collection="$futureEmployedAndUnemployedManagers">
    <thead>
        <th>Id</th>
        <th>Manager Name</th>
        <th>Date Employed</th>
        <th>Actions</th>
    </thead>
    <tbody>
        @forelse ($futureEmployedAndUnemployedManagers as $manager)
            <tr>
                <td>{{ $manager->id }}</td>
                <td>{{ $manager->full_name }}</td>
                <td>
                    @isset($manager->first_employed_at)
                        {{ $manager->first_employed_at->toDateString() }}
                    @else
                        TBD
                    @endisset
                </td>
                <td>
                    <x-actions-dropdown>
                        <x-buttons.view :route="route('managers.show', $manager)" />
                        <x-buttons.edit :route="route('managers.edit', $manager)" />
                        <x-buttons.delete :route="route('managers.destroy', $manager)" />
                        <x-buttons.employ :route="route('managers.employ', $manager)" />
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
