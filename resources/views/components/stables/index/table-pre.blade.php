<x-layouts.table-header :title="__('stables.index_title')" :subtitle="__('stables.index_description')">
    <x-slot:actions>
        @can('create', \App\Models\Roster\Stables\Stable::class)
            <x-button
                variant="ringside"
                size="md"
                class="min-h-11 shrink-0"
                @click="$dispatch('openModal', { component: 'stables.modals.form-modal' })"
            >
                <x-heroicon-o-plus class="size-4" aria-hidden="true" />
                {{ __('stables.add') }}
            </x-button>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>
