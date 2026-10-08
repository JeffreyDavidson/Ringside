<x-layouts.table-header :title="__('managers.index_title')" :subtitle="__('managers.index_description')">
    <x-slot:actions>
        @can('create', \App\Models\Roster\Managers\Manager::class)
            <x-button
                variant="ringside"
                size="md"
                class="min-h-11"
                @click="$dispatch('openModal', { component: 'managers.modals.form-modal' })"
            >
                {{ __('managers.add') }}
            </x-button>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>
