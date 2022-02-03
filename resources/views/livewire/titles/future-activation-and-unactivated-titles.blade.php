<div class="pt-3 mb-5 card card-flush mb-xl-10">
    @include('livewire.titles.partials.header')
    <div class="py-4 card-body">
        <x-data-table :collection="$futureActivationAndUnactivatedTitles">
            <x-table.row-heading>
                <x-table.checkbox-heading />
                <th>Title Name</th>
                <th>Status</th>
                <th>Date Introduced</th>
                <x-table.actions-heading />
            </x-table.row-heading>
            <x-table.body>
                @forelse ($futureActivationAndUnactivatedTitles as $title)
                    <tr>
                        <x-table.cell-checkbox value="{{ $title->id }}" />
                        <x-table.cell-link link="{{ route('titles.show', $title) }}" text="{{ $title->name }}" />
                        <td><div class="badge badge-light-warning">{{ $title->status->label }}</div></td>
                        <td>
                            @isset($title->first_activated_at)
                                {{ $title->activatedAt->toDateString() }}
                            @else
                                TBD
                            @endisset
                        </td>
                        <x-table.actions-cell>
                            @include('titles.partials.action-cell', [
                                'title' => $title,
                                'actions' => collect(['activate'])
                            ])
                        </x-table.actions-cell>
                    </tr>
                @empty
                    <tr><td colspan="4">No future activated or unactivated titles found.</td></tr>
                @endforelse
            </x-table.body>
        </x-data-table>
    </div>
</div>
