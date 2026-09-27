<x-layouts.table-header :title="__('promotions.index_title')" :subtitle="__('promotions.index_description')">
    <x-slot:actions>
        @can('create', \App\Models\Promotions\Promotion::class)
            <x-button
                variant="ringside"
                size="md"
                class="min-h-11 shrink-0"
                @click="$dispatch('openModal', { component: 'promotions.modals.form-modal' })"
            >
                {{ __('promotions.create') }}
            </x-button>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>
