<x-card>
    <x-slot name="header">
        @include('livewire.tag-teams.partials.header')
    </x-slot>

    <x-card.body class="pt-0">
        <div class="table-responsive">
            <x-table class="table-row-dashed fs-6 gy-5 dataTable no-footer">
                <x-slot name="head">
                    <x-table.heading class="w-10px pe-2 sorting_disabled">
                        <x-form.inputs.checkbox wire:model="selectPage"/>
                    </x-table.heading>
                    <x-table.heading
                            sortable
                            multi-column
                            wire:click="sortBy('name')"
                            :direction="$sorts['name'] ?? null"
                            class="min-w-125px sorting">Tag Team Name
                    </x-table.heading>
                    <x-table.heading
                            sortable
                            multi-column
                            wire:click="sortBy('status')"
                            :direction="$sorts['status'] ?? null"
                            class="min-w-125px sorting">Status
                    </x-table.heading>
                    <x-table.heading
                        sortable
                        multi-column
                        wire:click="sortBy('wrestlerA')"
                        :direction="$sorts['wrestlerA'] ?? null"
                        class="min-w-125px sorting">Tag Team Partner
                    </x-table.heading>
                    <x-table.heading
                        sortable
                        multi-column
                        wire:click="sortBy('wrestlerB')"
                        :direction="$sorts['wrestlerB'] ?? null"
                        class="min-w-125px sorting">Tag Team Partner
                    </x-table.heading>
                    <x-table.heading
                        sortable
                        multi-column
                        wire:click="sortBy('combined_weight')"
                        :direction="$sorts['combined_weight'] ?? null"
                        class="min-w-125px sorting">Combined Weight
                    </x-table.heading>
                    <x-table.heading
                        sortable
                        multi-column
                        wire:click="sortBy('start_date')"
                        :direction="$sorts['start_date'] ?? null"
                        class="min-w-125px sorting">Start Date
                    </x-table.heading>
                    <x-table.heading class="text-end min-w-70px sorting_disabled">Actions</x-table.heading>
                </x-slot>
                <x-slot name="body">
                    @forelse ($tagTeams as $tagTeam)
                        <x-table.row
                                :class="$loop->odd ? 'odd' : 'even'"
                                wire:loading.class.delay="opacity-50"
                                wire:key="row-{{ $tagTeam->id }}"
                        >
                            <x-table.cell>
                                <x-form.inputs.checkbox wire:model="selected" value="{{ $tagTeam->id }}"/>
                            </x-table.cell>

                            <x-table.cell>
                                <a
                                        class="mb-1 text-gray-800 text-hover-primary"
                                        href="{{ route('tag-teams.show', $tagTeam) }}"
                                >{{ $tagTeam->name }}</a>
                            </x-table.cell>

                            <x-table.cell>
                                <div class="badge badge-{{ $tagTeam->status->color() }}">{{ $tagTeam->status->label() }}</div>
                            </x-table.cell>

                            <x-table.cell>
                                <div>{{ $tagTeam->currentWrestlers->first()->name ?? 'No wrestler chosen' }}</div>
                            </x-table.cell>

                            <x-table.cell>
                                <div>{{ $tagTeam->currentWrestlers->last()->name ?? 'No wrestler chosen' }}</div>
                            </x-table.cell>

                            <x-table.cell>
                                <div>{{ $tagTeam->combined_weight }}</div>
                            </x-table.cell>

                            <x-table.cell>
                                {{ $tagTeam->startedAt?->toDateString() ?? 'No Start Date Set' }}
                            </x-table.cell>

                            <x-table.cell class="text-end">
                                @include('livewire.tag-teams.partials.action-cell')
                            </x-table.cell>

                        </x-table.row>
                    @empty
                        <x-table.row>
                            <x-table.cell colspan="6">
                                <div class="flex items-center justify-center space-x-2">
                                    <span class="py-8 text-xl font-medium text-cool-gray-400">No tag teams found...</span>
                                </div>
                            </x-table.cell>
                        </x-table.row>
                    @endforelse
                </x-slot>
            </x-table>
        </div>

        <div class="row">
            <div class="col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start"></div>
            <div class="col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end">
                {{ $tagTeams->links() }}
            </div>
        </div>
    </x-card.body>
</x-card>
