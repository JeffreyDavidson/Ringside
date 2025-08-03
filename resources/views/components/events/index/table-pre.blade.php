<x-layouts.table-header title="Events">
    <x-slot:actions>
        @can('create', \App\Models\Event::class)
            <x-buttons.primary size="sm" @click="$dispatch('openModal', { component: 'events.modals.form-modal' })">Add
                Event</x-buttons.primary>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>

<x-tables.meta-data enum="\App\Enums\EventStatus" />
