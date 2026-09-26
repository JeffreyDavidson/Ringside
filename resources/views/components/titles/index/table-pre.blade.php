<x-layouts.table-header :title="__('titles.index_title')" :subtitle="__('titles.index_description')">
    <x-slot:actions>
        @can('create', \App\Models\Titles\Title::class)
            <x-button
                variant="ringside"
                size="md"
                class="min-h-11 shrink-0"
                @click="$dispatch('openModal', { component: 'titles.modals.form-modal' })"
            >
                <x-heroicon-o-plus class="size-4" aria-hidden="true" />
                {{ __('titles.add') }}
            </x-button>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>
