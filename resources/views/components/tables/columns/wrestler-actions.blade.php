<x-tables.row-actions-menu :label="'Actions for '.$wrestler->name" menu-label="Wrestler actions">
    <li class="m-0 flex flex-col p-0">
        <a
            class="hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 items-center gap-3 px-3 text-sm focus-visible:outline-2"
            x-on:click="open = false"
            href="{{ route('wrestlers.show', $wrestler) }}"
        >
            <x-heroicon-m-eye class="text-ringside-muted size-5" aria-hidden="true" />
            <span>View</span>
        </a>
    </li>
    <li aria-hidden="true" class="border-ringside-line my-1 border-t"></li>
    @can('update', $wrestler)
        <li class="m-0 flex flex-col p-0">
            <a
                class="hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 w-full items-center gap-3 px-3 text-start text-sm focus-visible:outline-2"
                x-on:click="open = false"
                href="{{ route('wrestlers.edit.edit', $wrestler) }}"
            >
                <x-heroicon-m-pencil-square class="text-ringside-muted size-5" aria-hidden="true" />
                <span>Edit</span>
            </a>
        </li>
        <li aria-hidden="true" class="border-ringside-line my-1 border-t"></li>
    @endcan
    @can('delete', $wrestler)
        <li class="m-0 flex flex-col p-0">
            <button
                type="button"
                class="text-ringside-signal-soft hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 w-full items-center gap-3 px-3 text-start text-sm focus-visible:outline-2"
                x-on:click="open = false"
                wire:click="delete({{ $wrestler->id }})"
                wire:confirm="Remove {{ $wrestler->name }}?"
            >
                <x-heroicon-m-trash class="size-5" aria-hidden="true" />
                <span>Remove</span>
            </button>
        </li>
    @endcan
</x-tables.row-actions-menu>
