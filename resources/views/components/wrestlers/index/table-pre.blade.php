<x-layouts.table-header :title="__('wrestlers.index_title')" :subtitle="__('wrestlers.index_description')">
    <x-slot:actions>
        @can('create', \App\Models\Roster\Wrestlers\Wrestler::class)
            <x-button
                variant="ringside"
                size="md"
                class="min-h-11 shrink-0"
                @click="$dispatch('openModal', { component: 'wrestlers.modals.form-modal' })"
            >
                <x-heroicon-o-plus class="size-4" aria-hidden="true" />
                {{ __('wrestlers.add') }}
            </x-button>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>
