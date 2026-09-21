<div class="flex" x-data="{ open: false }">
    <div class="m-0 flex flex-col p-0">
        <button
            x-ref="button"
            @click="open = ! open"
            class="flex w-8 shrink-0 grow cursor-pointer items-center justify-center gap-0 p-0 hover:border-transparent hover:bg-gray-200 hover:text-gray-800 hover:shadow-none"
        >
            <x-heroicon-m-ellipsis-vertical class="size-5" />
        </button>
        <div
            x-show="open"
            @click.outside="open = false"
            x-anchor.bottom-start="$refs.button"
            class="z-[105] m-0 w-full max-w-[175px] rounded-xl border border-solid border-gray-200 bg-white py-2.5 shadow-[0_7px_18px_0px_rgba(0,0,0,0.09)]"
        >
            <ul>
                <li class="m-0 flex flex-col p-0">
                    <a
                        class="group m-0 ms-2.5 me-2.5 flex grow cursor-pointer items-center rounded-md p-2.5 hover:bg-gray-100"
                        x-on:click="open = false"
                        href="{{ route('wrestlers.show', $wrestler) }}"
                    >
                        <span class="me-2.5 flex shrink-0 items-center">
                            <x-heroicon-m-magnifying-glass class="group-hover:text-primary size-5 text-gray-500" />
                        </span>
                        <span class="text-2sm flex grow items-center font-medium text-gray-800">View</span>
                    </a>
                </li>
                <div class="my-2.5 border-b border-solid border-gray-200"></div>
                @can('update', $wrestler)
                    <li class="m-0 flex flex-col p-0">
                        <button
                            class="group m-0 ms-2.5 me-2.5 flex grow cursor-pointer items-center rounded-md p-2.5 hover:bg-gray-100"
                            x-on:click="open = false"
                            wire:click="$dispatch('openModal', { component: 'wrestlers.modals.form-modal', arguments: { 'modelId': '{{ $wrestler->id }}' }})"
                        >
                            <span class="me-2.5 flex shrink-0 items-center">
                                <x-heroicon-m-pencil-square class="group-hover:text-primary size-5 text-gray-500" />
                            </span>
                            <span class="text-2sm flex grow items-center font-medium text-gray-800">Edit</span>
                        </button>
                    </li>
                    <div class="my-2.5 border-b border-solid border-gray-200"></div>
                @endcan
                @can('delete', $wrestler)
                    <li class="m-0 flex flex-col p-0">
                        <a
                            class="group m-0 ms-2.5 me-2.5 flex grow cursor-pointer items-center rounded-md p-2.5 hover:bg-gray-100"
                            x-on:click="open = false"
                            wire:click="delete({{ $wrestler->id }})"
                            wire:confirm
                        >
                            <span class="me-2.5 flex shrink-0 items-center">
                                <x-heroicon-m-trash class="group-hover:text-primary size-5 text-gray-500" />
                            </span>
                            <span class="text-2sm flex grow items-center font-medium text-gray-800">Remove</span>
                        </a>
                    </li>
                @endcan
            </ul>
        </div>
    </div>
</div>
