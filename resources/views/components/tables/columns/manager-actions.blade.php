<div class="flex justify-center" x-data="{ open: false }">
    <div class="relative">
        <button
            type="button"
            x-ref="button"
            @click="open = ! open"
            :aria-expanded="open"
            aria-haspopup="menu"
            aria-label="Actions for {{ $manager->full_name }}"
            class="text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink focus-visible:outline-ringside-ink inline-flex size-9 items-center justify-center focus-visible:outline-2 focus-visible:outline-offset-2"
        >
            <x-heroicon-m-ellipsis-vertical class="size-5" aria-hidden="true" />
        </button>
        <div
            x-cloak
            x-show="open"
            @click.outside="open = false"
            @keydown.escape.stop="open = false"
            x-anchor.fixed.bottom-start="$refs.button"
            x-transition.origin.top.left
            class="border-ringside-line bg-ringside-surface-header text-ringside-ink z-[105] m-0 w-48 border p-1 shadow-xl"
            role="menu"
            aria-label="Manager actions"
        >
            <ul class="m-0 list-none p-0">
                <li class="m-0 flex flex-col p-0">
                    <a
                        role="menuitem"
                        class="hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 items-center gap-3 px-3 text-sm focus-visible:outline-2"
                        x-on:click="open = false"
                        href="{{ route('managers.show', $manager) }}"
                    >
                        <x-heroicon-m-eye class="text-ringside-muted size-5" aria-hidden="true" />
                        <span>View</span>
                    </a>
                </li>
                @can('update', $manager)
                    <li role="separator" class="border-ringside-line my-1 border-t"></li>
                    <li class="m-0 flex flex-col p-0">
                        <button
                            type="button"
                            role="menuitem"
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
                    <li role="separator" class="border-ringside-line my-1 border-t"></li>
                    <li class="m-0 flex flex-col p-0">
                        <button
                            type="button"
                            role="menuitem"
                            class="text-ringside-signal-soft hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 w-full items-center gap-3 px-3 text-start text-sm focus-visible:outline-2"
                            x-on:click="open = false"
                            wire:click="delete({{ $manager->id }})"
                            wire:confirm
                        >
                            <x-heroicon-m-trash class="size-5" aria-hidden="true" />
                            <span>Remove</span>
                        </button>
                    </li>
                @endcan
            </ul>
        </div>
    </div>
</div>
