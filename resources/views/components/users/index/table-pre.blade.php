<x-layouts.table-header :title="__('users.index_title')" :subtitle="__('users.index_description')">
    <x-slot:actions>
        @can('create', \App\Models\Users\User::class)
            <x-button
                variant="ringside"
                size="md"
                class="min-h-11 shrink-0 whitespace-nowrap"
                @click="$dispatch('openModal', { component: 'users.modals.form-modal' })"
            >
                {{ __('users.add') }}
            </x-button>
        @endcan
    </x-slot:actions>
</x-layouts.table-header>
