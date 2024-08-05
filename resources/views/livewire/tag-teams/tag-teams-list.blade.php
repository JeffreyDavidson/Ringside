<x-card>
    <x-card.header class="pt-6">
        <x-card.title>
            <x-search resource="Tag Teams" />
        </x-card.title>

        <x-card.toolbar>
            <card.toolbar.actions>
                <x-buttons.create route="{{ route('tag-teams.create') }}" resource="Tag Team" />
            </card.toolbar.actions>
        </x-card.toolbar>
    </x-card.header>

    <x-card.body class="pt-0">
        <x-table.wrapper>
            <x-table>
                <x-table.head>
                    <x-table.heading class="w-10px pe-2 sorting_disabled"><x-form.inputs.checkbox wire:model="selectPage"/></x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('name')" :direction="$sorts['name'] ?? null" class="min-w-125px sorting">Tag Team Name</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('status')" :direction="$sorts['status'] ?? null" class="min-w-125px sorting">Status</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('wrestlerA')" :direction="$sorts['wrestlerA'] ?? null" class="min-w-125px sorting">Current Tag Team Partners</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('combined_weight')" :direction="$sorts['combined_weight'] ?? null" class="min-w-125px sorting">Combined Weigh</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('start_date')" :direction="$sorts['start_date'] ?? null" class="min-w-125px sorting">Start Date</x-table.heading>
                    <x-table.heading class="text-end min-w-70px sorting_disabled">Actions</x-table.heading>
                </x-table.head>
                <x-table.body>
                    @forelse ($tagTeams as $tagTeam)
                        <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:loading.class.delay="opacity-50" wire:key="row-{{ $tagTeam->id }}">
                            <x-table.cell><x-form.inputs.checkbox wire:model="selected" value="{{ $tagTeam->id }}"/></x-table.cell>
                            <x-table.cell><a class="mb-1 text-gray-800 text-hover-primary" href="{{ route('tag-teams.show', $tagTeam) }}">{{ $tagTeam->name }}</a></x-table.cell>
                            <x-table.cell><div class="badge badge-{{ $tagTeam->status->color() }}">{{ $tagTeam->status->label() }}</div></x-table.cell>
                            <x-table.cell>
                                @if ($tagTeam->currentWrestlers->isNotEmpty())
                                    @if ($tagTeam->currentWrestlers->count() === 2)
                                        <div>{{ $tagTeam->currentWrestlers->pluck('name')->join(', ', ' and ') }}</div>
                                    @else
                                        <div>{{ $tagTeam->currentWrestlers->first()->name }} and TBD</div>
                                    @endif
                                @else
                                    <div>No Wrestlers Assigned</div>
                                @endif
                            </x-table.cell>
                            <x-table.cell><div>{{ $tagTeam->combined_weight }} lbs.</div></x-table.cell>
                            <x-table.cell>{{ $tagTeam->startedAt?->toDateString() ?? 'No Start Date Set' }}</x-table.cell>
                            <x-table.cell class="text-end">
                                @include('livewire.tag-teams.partials.action-cell')
                            </x-table.cell>
                        </x-table.row>
                    @empty
                        <x-table.row-no-data colspan="7"/>
                    @endforelse
                </x-table.body>
                <x-table.foot/>
            </x-table>
            <x-table.footer :collection="$tagTeams" />
        </x-table.wrapper>
    </x-card.body>
</x-card>
