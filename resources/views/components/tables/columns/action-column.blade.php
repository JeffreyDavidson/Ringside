<div x-data="{ open: false }">

    <button x-ref="button" @click="open = ! open" class="flex items-center grow cursor-pointer hover:bg-gray-200 hover:border-transparent hover:shadow-none hover:text-gray-800">
        <i class="ki-filled ki-dots-vertical text-lg"></i>
    </button>
    <div x-show="open" x-anchor.bottom-start="$refs.button" class="px-2 bg-white z-50	">
        <ul>
            <li><a x-on:click="open = false;" href="{{ route($path . '.show', $rowId) }}">View</a></li>
            <li><button x-on:click="open = false;" wire:click="$dispatch('openModal', { component: 'wrestlers.wrestler-modal', arguments: { wrestlerId: {{ $rowId }} }})">Edit</button>            </li>
            <li><a x-on:click="open = false;" wire:click="delete({{ $rowId}})" wire:confirm>Remove</a></li>
        </ul>
    </div>
</div>