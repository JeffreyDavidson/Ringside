<div class="flex" x-data="{ open: false }">
    <div class="flex flex-col m-0 p-0">
        <button x-ref="button" @click="open = ! open"
            class="flex items-center grow cursor-pointer w-8 hover:bg-gray-200 hover:border-transparent hover:shadow-none hover:text-gray-800 justify-center shrink-0 p-0 gap-0">
            <i class="ki-filled ki-dots-vertical text-lg"></i>
        </button>
        <div x-show="open" @click.outside="open = false" x-anchor.bottom-start="$refs.button"
            class="m-0 py-2.5 border border-solid border-gray-200 bg-white rounded-xl shadow-[0_7px_18px_0px_rgba(0,0,0,0.09)] w-full max-w-[175px] z-[105]">
            <ul>
                <li class="flex flex-col m-0 p-0">
                    <a class="group flex items-center grow cursor-pointer m-0 p-2.5 ms-2.5 me-2.5 rounded-md hover:bg-gray-100"
                        x-on:click="open = false;" href="{{ route('wrestlers.show', $wrestler) }}">
                        <span class="flex items-center shrink-0 me-2.5">
                            <i class="ki-filled ki-search-list text-gray-500 text-lg group-hover:text-primary"></i>
                        </span>
                        <span class="flex items-center grow font-medium text-2sm text-gray-800">View</span>
                    </a>
                </li>
                <div class="border-b border-solid border-gray-200 my-2.5"></div>
                @can('update', $wrestler)
                <li class="flex flex-col m-0 p-0">
                    <button
                        class="group flex items-center grow cursor-pointer m-0 p-2.5 ms-2.5 me-2.5 rounded-md hover:bg-gray-100"
                        x-on:click="open = false;"
                        wire:click="$dispatch('openModal', { component: 'wrestlers.modals.form-modal', arguments: { 'modelId': '{{ $wrestler->id }}' }})">
                        <span class="flex items-center shrink-0 me-2.5">
                            <i class="ki-filled ki-pencil text-gray-500 text-lg group-hover:text-primary"></i>
                        </span>
                        <span class="flex items-center grow font-medium text-2sm text-gray-800">Edit</span>
                    </button>
                </li>
                <div class="border-b border-solid border-gray-200 my-2.5"></div>
                @endcan
                @can('delete', $wrestler)
                <li class="flex flex-col m-0 p-0">
                    <a class="group flex items-center grow cursor-pointer m-0 p-2.5 ms-2.5 me-2.5 rounded-md hover:bg-gray-100"
                        x-on:click="open = false;" wire:click="delete({{ $wrestler->id }})" wire:confirm>
                        <span class="flex items-center shrink-0 me-2.5">
                            <i class="ki-filled ki-trash text-gray-500 text-lg group-hover:text-primary"></i>
                        </span>
                        <span class="flex items-center grow font-medium text-2sm text-gray-800">Remove</span>
                    </a>
                </li>
                @endcan
            </ul>
        </div>
    </div>
</div>