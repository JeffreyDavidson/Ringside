<div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
    <div class="flex flex-col justify-center gap-2">
        <x-tables.meta-data />
    </div>
    <div class="flex items-center gap-2.5">
        @can('create', \App\Models\Venue::class)
            <x-buttons.primary size="sm" @click="$dispatch('openModal', { component: 'venues.modals.form-modal' })">Add
                Venue</x-buttons.primary>
        @endcan
    </div>
</div>
