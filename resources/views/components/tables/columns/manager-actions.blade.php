<x-tables.row-actions-menu :label="'Actions for '.$manager->full_name" menu-label="Manager actions">
    <li class="m-0 flex flex-col p-0">
        <a
            class="hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 items-center gap-3 px-3 text-sm focus-visible:outline-2"
            x-on:click="open = false"
            href="{{ route('managers.show', $manager) }}"
        >
            <x-heroicon-m-eye class="text-ringside-muted size-5" aria-hidden="true" />
            <span>View</span>
        </a>
    </li>
    @can('update', $manager)
        <li aria-hidden="true" class="border-ringside-line my-1 border-t"></li>
        <li class="m-0 flex flex-col p-0">
            <button
                type="button"
                class="hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 w-full items-center gap-3 px-3 text-start text-sm focus-visible:outline-2"
                x-on:click="open = false"
                wire:click="$dispatch('openModal', { component: 'managers.modals.form-modal', arguments: { 'modelId': '{{ $manager->id }}' }})"
            >
                <x-heroicon-m-pencil-square class="text-ringside-muted size-5" aria-hidden="true" />
                <span>Edit</span>
            </button>
        </li>
    @endcan
    @can('delete', $manager)
        <li aria-hidden="true" class="border-ringside-line my-1 border-t"></li>
        <li class="m-0 flex flex-col p-0">
            <button
                type="button"
                class="text-ringside-signal-soft hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 w-full items-center gap-3 px-3 text-start text-sm focus-visible:outline-2"
                x-on:click="open = false"
                wire:click="delete({{ $manager->id }})"
                wire:confirm="Remove {{ $manager->full_name }}?"
            >
                <x-heroicon-m-trash class="size-5" aria-hidden="true" />
                <span>Remove</span>
            </button>
        </li>
    @endcan
</x-tables.row-actions-menu>
