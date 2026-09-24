<x-tables.row-actions-menu :label="'Actions for '.$title->name" menu-label="Title actions">
    @can('view', $title)
        <li class="m-0 flex flex-col p-0">
            <a
                class="hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 items-center gap-3 px-3 text-sm focus-visible:outline-2"
                x-on:click="open = false"
                href="{{ route('titles.show', $title) }}"
            >
                <x-heroicon-m-eye class="text-ringside-muted size-5" aria-hidden="true" />
                <span>View</span>
            </a>
        </li>
    @endcan
    @can('update', $title)
        <li aria-hidden="true" class="border-ringside-line my-1 border-t"></li>
        <li class="m-0 flex flex-col p-0">
            <button
                type="button"
                class="hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 w-full items-center gap-3 px-3 text-start text-sm focus-visible:outline-2"
                x-on:click="open = false"
                wire:click="$dispatch('openModal', { component: 'titles.modals.form-modal', arguments: { 'modelId': '{{ $title->id }}' }})"
            >
                <x-heroicon-m-pencil-square class="text-ringside-muted size-5" aria-hidden="true" />
                <span>Edit</span>
            </button>
        </li>
    @endcan
    @can('delete', $title)
        <li aria-hidden="true" class="border-ringside-line my-1 border-t"></li>
        <li class="m-0 flex flex-col p-0">
            <button
                type="button"
                class="text-ringside-signal-soft hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 w-full items-center gap-3 px-3 text-start text-sm focus-visible:outline-2"
                x-on:click="open = false"
                wire:click="delete({{ $title->id }})"
                wire:confirm="Remove {{ $title->name }}?"
            >
                <x-heroicon-m-trash class="size-5" aria-hidden="true" />
                <span>Remove</span>
            </button>
        </li>
    @endcan
</x-tables.row-actions-menu>
