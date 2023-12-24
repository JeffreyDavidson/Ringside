<x-card>
    <x-slot name="header">
        @include('livewire.events.partials.header')
    </x-slot>

    <x-card.body class="pt-0">
        <div class="table-responsive">
            <x-table class="table-row-dashed fs-6 gy-5 dataTable no-footer">
                <x-slot name="head">
                    <x-table.heading class="w-10px pe-2 sorting_disabled">
                        <x-form.inputs.checkbox wire:model="selectPage" />
                    </x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('name')" :direction="$sorts['name'] ?? null" class="min-w-125px sorting">Event Name</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('status')" :direction="$sorts['status'] ?? null" class="min-w-125px sorting">Status</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">Created At</x-table.heading>
                    <x-table.heading class="text-end min-w-70px sorting_disabled">Actions</x-table.heading>
                </x-slot>
                <x-slot name="body">
                    @forelse ($events as $event)
                        <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:loading.class.delay="opacity-50" wire:key="row-{{ $event->id }}">
                            <x-table.cell>
                                <x-form.inputs.checkbox wire:model="selected" value="{{ $event->id }}" />
                            </x-table.cell>

                            <x-table.cell>
                                <a class="text-gray-800 text-hover-primary" href="{{ route('events.show', $event) }}">{{ $event->name }}</a>
                            </x-table.cell>

                            <x-table.cell>
                                <div class="badge badge-{{ $event->status->color() }}">
                                    {{ $event->status->label() }}
                                </div>
                            </x-table.cell>

                            <x-table.cell>
                                {{ $event->created_at->toFormattedDateString() }}
                            </x-table.cell>

                            <x-table.cell class="text-end">
                                <x-actions-dropdown>
                                    @can('restore', $event)
                                        <x-buttons.restore :route="route('events.restore', $event)" />
                                    @endcan
                                </x-actions-dropdown>
                            </x-table.cell>

                        </x-table.row>
                    @empty
                        <x-table.row>
                            <x-table.cell colspan="6">
                                <div class="flex items-center justify-center space-x-2">
                                    <span class="py-8 text-xl font-medium text-cool-gray-400">No deleted events found...</span>
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
                {{ $events->links() }}
            </div>
        </div>
    </x-card.body>
</x-card>
