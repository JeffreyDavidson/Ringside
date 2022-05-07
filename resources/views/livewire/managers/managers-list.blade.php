<div class="card">
    @include('livewire.managers.partials.header')
    <div class="py-4 card-body">
        <div class="table-responsive">
            <x-table class="table-row-dashed fs-6 gy-5 dataTable no-footer">
                <x-slot name="head">
                    <x-table.heading class="w-10px pe-2 sorting_disabled">
                        <x-input.checkbox wire:model="selectPage" />
                    </x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('name')" :direction="$sorts['name'] ?? null" class="min-w-125px sorting">manager Name</x-table.heading>
                    <x-table.heading sortable multi-column wire:click="sortBy('status')" :direction="$sorts['status'] ?? null" class="min-w-125px sorting">Status</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">Created At</x-table.heading>
                    <x-table.heading class="text-end min-w-70px sorting_disabled">Actions</x-table.heading>
                </x-slot>
                <x-slot name="body">
                    @forelse ($managers as $manager)
                        <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:loading.class.delay="opacity-50" wire:key="row-{{ $manager->id }}">
                            <x-table.cell>
                                <x-input.checkbox wire:model="selected" value="{{ $manager->id }}" />
                            </x-table.cell>

                            <x-table.cell>
                                <a class="mb-1 text-gray-800 text-hover-primary" href="{{ route('managers.show', $manager) }}">{{ $manager->full_name }}</a>
                            </x-table.cell>

                            <x-table.cell>
                                <div class="badge badge-{{ $manager->status->getBadgeColor() }}">
                                    {{ $manager->status->label }}
                                </div>
                            </x-table.cell>

                            <x-table.cell>
                                {{ $manager->created_at->toFormattedDateString() }}
                            </x-table.cell>

                            <x-table.cell class="text-end">
                                @include('livewire.managers.partials.action-cell')
                            </x-table.cell>

                        </x-table.row>
                    @empty
                        <x-table.row>
                            <x-table.cell colspan="6">
                                <div class="flex items-center justify-center space-x-2">
                                    <span class="py-8 text-xl font-medium text-cool-gray-400">No managers found...</span>
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
                {{ $managers->links() }}
            </div>
        </div>
    </div>
</div>
