<x-tables.row-actions-menu :label="'Actions for '.$venue->name" menu-label="Venue actions">
    @can('view', $venue)
        <li class="m-0 flex flex-col p-0">
            <a
                class="hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 items-center gap-3 px-3 text-sm focus-visible:outline-2"
                x-on:click="open = false"
                href="{{ route('venues.show', $venue) }}"
            >
                <x-heroicon-m-eye class="text-ringside-muted size-5" aria-hidden="true" />
                <span>View</span>
            </a>
        </li>
    @endcan
    @can('update', $venue)
        <li aria-hidden="true" class="border-ringside-line my-1 border-t"></li>
        <li class="m-0 flex flex-col p-0">
            <button
                type="button"
                class="hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 w-full items-center gap-3 px-3 text-start text-sm focus-visible:outline-2"
                x-on:click="open = false"
                wire:click="$dispatch('openModal', { component: 'venues.modals.form-modal', arguments: { 'modelId': '{{ $venue->id }}' }})"
            >
                <x-heroicon-m-pencil-square class="text-ringside-muted size-5" aria-hidden="true" />
                <span>Edit</span>
            </button>
        </li>
    @endcan
    @can('delete', $venue)
        <li aria-hidden="true" class="border-ringside-line my-1 border-t"></li>
        <li class="m-0 flex flex-col p-0">
            <button
                type="button"
                class="text-ringside-signal-soft hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 w-full items-center gap-3 px-3 text-start text-sm focus-visible:outline-2"
                x-on:click="open = false"
                wire:click="delete({{ $venue->id }})"
                wire:confirm="Remove {{ $venue->name }}?"
            >
                <x-heroicon-m-trash class="size-5" aria-hidden="true" />
                <span>Remove</span>
            </button>
        </li>
    @endcan
</x-tables.row-actions-menu>
