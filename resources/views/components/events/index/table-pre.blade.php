<x-layouts.table-header :title="__('events.index_title')" :subtitle="__('events.index_description')">
    <x-slot:actions>
        @can('create', \App\Models\Events\Event::class)
            <x-button
                variant="ringside"
                size="md"
                class="min-h-11"
                @click="$dispatch('openModal', { component: 'events.modals.form-modal' })"
            >
                {{ __('events.add') }}
            </x-button>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>
