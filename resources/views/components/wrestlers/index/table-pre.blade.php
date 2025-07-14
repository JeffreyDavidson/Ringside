<div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
    <div class="flex flex-col justify-center gap-2">
        <x-tables.meta-data enum="\App\Enums\Shared\EmploymentStatus" />
    </div>
    <div class="flex items-center gap-2.5">
        @can('create', \App\Models\Wrestler::class)
            <x-buttons.primary size="sm"
                @click="$dispatch('openModal', { component: 'wrestlers.modals.form-modal' })">Add
                Wrestler</x-buttons.primary>
        @endcan
    </div>
</div>
