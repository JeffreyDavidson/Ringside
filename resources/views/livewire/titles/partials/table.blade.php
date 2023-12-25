<x-datatable>
    <x-slot name="head">
        <x-table.heading class="w-10px pe-2 sorting_disabled">
            <x-form.inputs.checkbox wire:model="selectPage" />
        </x-table.heading>
        <x-table.heading sortable multi-column wire:click="sortBy('name')" :direction="$sorts['name'] ?? null" class="min-w-125px sorting">Title Name</x-table.heading>
        <x-table.heading sortable multi-column wire:click="sortBy('status')" :direction="$sorts['status'] ?? null" class="min-w-125px sorting">Status</x-table.heading>
        <x-table.heading class="min-w-70px sorting_disabled">Current Champion</x-table.heading>
        <x-table.heading class="text-end min-w-70px sorting_disabled">Actions</x-table.heading>
    </x-slot>
    <x-slot name="body">
        @forelse ($titles as $title)
            <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:loading.class.delay="opacity-50" wire:key="row-{{ $title->id }}">
                <x-table.cell>
                    <x-form.inputs.checkbox wire:model="selected" value="{{ $title->id }}" />
                </x-table.cell>

                <x-table.cell>
                    <a class="text-gray-800 text-hover-primary" href="{{ route('titles.show', $title) }}">{{ $title->name }}</a>
                </x-table.cell>

                <x-table.cell>
                    <div class="badge badge-{{ $title->status->color() }}">
                        {{ $title->status->label() }}
                    </div>
                </x-table.cell>

                <x-table.cell>
                    {{ $title->currentChampionship->champion->display_name ?? 'Vacant' }}
                </x-table.cell>

                <x-table.cell class="text-end">
                    @include('livewire.titles.partials.action-cell')
                </x-table.cell>

            </x-table.row>
        @empty
            <x-table.row>
                <x-table.cell colspan="6">
                    <div class="flex items-center justify-center space-x-2">
                        <span class="py-8 text-xl font-medium text-cool-gray-400">No titles found...</span>
                    </div>
                </x-table.cell>
            </x-table.row>
        @endforelse
    </x-slot>

    <x-slot name="footer">
        <x-table.footer :collection="$titles" />
    </x-slot>
</x-datatable>
