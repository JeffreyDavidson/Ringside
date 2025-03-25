<div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
    <div class="flex flex-col justify-center gap-2">
        <x-tables.meta-data enum="\App\Enums\ActivationStatus" />
    </div>
    <div class="flex items-center gap-2.5">
        @can('create', \App\Models\Stable::class)
            <x-buttons.primary size="sm" @click="$dispatch('openModal', { component: 'stables.modals.form-modal' })">Add
                Stable</x-buttons.primary>
        @endcan
    </div>
</div>
