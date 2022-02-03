<div class="pt-3 mb-5 card card-flush mb-xl-10">
    @include('livewire.titles.partials.header')
    <div class="py-4 card-body">
        <x-data-table :collection="$inactiveTitles">
            <x-table.row-heading>
                <x-table.checkbox-heading />
                <th>Title Name</th>
                <th>Status</th>
                <th>Date Deactivated</th>
                <x-table.actions-heading />
            </x-table.row-heading>
            <x-table.body>
                @forelse ($inactiveTitles as $title)
                    <tr>
                        <x-table.cell-checkbox value="{{ $title->id }}" />
                        <x-table.cell-link link="{{ route('titles.show', $title) }}" text="{{ $title->name }}" />
                        <td><div class="badge badge-light-danger">{{ $title->status->label }}</div></td>
                        <td>{{ $title->activatedAt->toDateString() }}</td>
                        <x-table.actions-cell>
                            @include('titles.partials.action-cell', [
                                'title' => $title,
                                'actions' => collect(['activate'])
                            ])
                        </x-table.actions-cell>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No inactive titles found.</td>
                    </tr>
                @endforelse
            </x-table.body>
        </x-data-table>
    </div>
</div>
