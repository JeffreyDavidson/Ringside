<div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
    <div class="flex flex-col justify-center gap-2">
        <x-tables.meta-data enum="\App\Enums\Titles\TitleStatus" />
    </div>
    <div class="flex items-center gap-2.5">
        @can('create', \App\Models\Titles\Title::class)
            <x-buttons.primary size="sm" @click="$dispatch('openModal', { component: 'titles.modals.form-modal' })">
                Add Title</x-buttons.primary>
        @endcan
    </div>
</div>
