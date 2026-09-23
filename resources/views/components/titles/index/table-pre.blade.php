<x-layouts.table-header title="Titles" :subtitle="__('titles.index_description')">
    <x-slot:actions>
        @can('create', \App\Models\Titles\Title::class)
            <x-button
                variant="ringside"
                size="md"
                class="min-h-11"
                @click="$dispatch('openModal', { component: 'titles.modals.form-modal' })"
            >
                Add Title
            </x-button>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>

<x-tables.meta-data />
