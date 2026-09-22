<div class="flex" x-data="{ open: false }">
    <div class="m-0 flex flex-col p-0">
        <button
            x-ref="button"
            @click="open = ! open"
            class="text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink flex size-9 shrink-0 grow cursor-pointer items-center justify-center gap-0 p-0"
        >
            <x-heroicon-m-ellipsis-vertical class="size-5" />
        </button>
        <div
            x-show="open"
            @click.outside="open = false"
            x-anchor.bottom-start="$refs.button"
            class="border-ringside-line bg-ringside-surface-header z-[105] m-0 w-full max-w-[175px] border py-2.5 shadow-xl"
        >
            <ul>
                <li class="m-0 flex flex-col p-0">
                    <a
                        class="text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink group m-0 mx-2.5 flex grow cursor-pointer items-center p-2.5"
                        x-on:click="open = false"
                        href="{{ route($path . '.show', $rowId) }}"
                    >
                        <span class="me-2.5 flex shrink-0 items-center">
                            <x-heroicon-m-magnifying-glass class="size-5" />
                        </span>
                        <span class="flex grow items-center text-sm font-medium">View</span>
                    </a>
                </li>
                <div class="border-ringside-line my-2.5 border-b"></div>
                <li class="m-0 flex flex-col p-0">
                    <button
                        class="text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink group m-0 mx-2.5 flex grow cursor-pointer items-center p-2.5"
                        x-on:click="open = false"
                        wire:click="$dispatch('openModal', { component: '{{ $resourceName }}.modals.form-modal', arguments: { 'modelId': '{{ $rowId }}' }})"
                    >
                        <span class="me-2.5 flex shrink-0 items-center">
                            <x-heroicon-m-pencil-square class="size-5" />
                        </span>
                        <span class="flex grow items-center text-sm font-medium">Edit</span>
                    </button>
                </li>
                <div class="border-ringside-line my-2.5 border-b"></div>
                <li class="m-0 flex flex-col p-0">
                    <a
                        class="text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink group m-0 mx-2.5 flex grow cursor-pointer items-center p-2.5"
                        x-on:click="open = false"
                        wire:click="delete({{ $rowId }})"
                        wire:confirm
                    >
                        <span class="me-2.5 flex shrink-0 items-center">
                            <x-heroicon-m-trash class="size-5" />
                        </span>
                        <span class="flex grow items-center text-sm font-medium">Remove</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
