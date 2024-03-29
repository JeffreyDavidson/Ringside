
<x-card>
    <x-slot name="header">
        @include('livewire.wrestlers.partials.header')
    </x-slot>

    <x-card.body class="pt-0">
        <div class="table-responsive">
            <x-table class="table-row-dashed fs-6 gy-5 dataTable no-footer">
                <x-slot name="head">
                    <x-table.heading class="w-10px pe-2 sorting_disabled"><x-wrestlers.index.check-all/></x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('name')" :direction="$sorts['name'] ?? null" class="min-w-125px sorting">Wrestler Name</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('status')" :direction="$sorts['status'] ?? null" class="min-w-125px sorting">Status</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('height')" :direction="$sorts['height'] ?? null" class="min-w-125px sorting">Height</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('weight')" :direction="$sorts['weight'] ?? null" class="min-w-125px sorting">Weight</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('hometown')" :direction="$sorts['hometown'] ?? null" class="min-w-125px sorting">Hometown</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('hometown')" :direction="$sorts['start_date'] ?? null" class="min-w-125px sorting">Start Date</x-table.heading>
                    <x-table.heading class="text-end min-w-70px sorting_disabled">Actions</x-table.heading>
                </x-slot>
                <x-slot name="body">
                    @forelse ($wrestlers as $wrestler)
                        <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:loading.class.delay="opacity-50" wire:key="row-{{ $wrestler->id }}">
                            <x-table.cell>
                                <x-form.inputs.checkbox wire:model="selectedWrestlerIds" value="{{ $wrestler->id }}" />
                            </x-table.cell>

                            <x-table.cell>
                                <a class="text-gray-800 text-hover-primary" href="{{ route('wrestlers.show', $wrestler) }}">{{ $wrestler->name }}</a>
                            </x-table.cell>

                            <x-table.cell>
                                <div class="badge badge-{{ $wrestler->status->color() }}">
                                    {{ $wrestler->status->label() }}
                                </div>
                            </x-table.cell>

                            <x-table.cell>
                                {{ $wrestler->height }}
                            </x-table.cell>

                            <x-table.cell>
                                {{ $wrestler->weight }} lbs.
                            </x-table.cell>

                            <x-table.cell>
                                {{ $wrestler->hometown }}
                            </x-table.cell>

                            <x-table.cell>
                                {{ $wrestler->startedAt?->toDateString() ?? 'No Start Date Set' }}
                            </x-table.cell>

                            <x-table.cell class="text-end">
                                @include('livewire.wrestlers.partials.action-cell')
                            </x-table.cell>

                        </x-table.row>
                    @empty
                        <x-table.row>
                            <x-table.cell colspan="6">
                                <div class="flex items-center justify-center space-x-2">
                                    <span class="py-8 text-xl font-medium text-cool-gray-400">No wrestlers found...</span>
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
                {{ $wrestlers->links() }}
            </div>
        </div>
    </x-card.body>
</x-card>
