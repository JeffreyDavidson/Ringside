<x-layouts.table-header :title="__('referees.index_title')" :subtitle="__('referees.index_description')">
    <x-slot:actions>
        @can('create', \App\Models\Roster\Referees\Referee::class)
            <x-button
                variant="ringside"
                size="md"
                class="min-h-11"
                @click="$dispatch('openModal', { component: 'referees.modals.form-modal' })"
            >
                {{ __('referees.add') }}
            </x-button>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>
