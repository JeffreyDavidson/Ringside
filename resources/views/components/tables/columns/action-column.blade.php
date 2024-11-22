<x-menu>
    <x-menu.menu-item-dropdown>
        <x-menu.menu-toggle class="inline-flex items-center cursor-pointer leading-4 rounded-md border border-solid border-transparent outline-none h-8 ps-3 pe-3 font-medium text-xs justify-center shrink-0 p-0 gap-0 w-8 bg-transparent text-gray-700"/>
        <x-menu.menu-dropdown isDefault class="py-2.5 w-full max-w-[175px] hidden">
            @if ($links['view'] ?? true)
                <x-menu.menu-item>
                    <x-menu.menu-link href="{{ route($path . '.show', $rowId) }}">
                        <x-menu.menu-dropdown-icon icon="ki-search-list"/>
                        <x-menu.menu-dropdown-title>View</x-menu.menu-dropdown-title>
                    </x-menu.menu-link>
                </x-menu.menu-item>
            @endif

            <x-menu.menu-item-separator/>

            @if ($links['edit'] ?? true)
                <x-menu.menu-item>
                    <x-menu.menu-link href="{{ route($path . '.edit', $rowId) }}">
                        <x-menu.menu-dropdown-icon icon="ki-pencil"/>
                        <x-menu.menu-dropdown-title>Edit</x-menu.menu-dropdown-title>
                    </x-menu.menu-link>
                </x-menu.menu-item>
            @endif

            <x-menu.menu-item-separator/>

            @if ($links['delete'] ?? true)
                <x-menu.menu-item>
                    <x-menu.menu-link href="{{ route($path . '.destroy', $rowId) }}">
                        <x-menu.menu-dropdown-icon icon="ki-trash"/>
                        <x-menu.menu-dropdown-title>Remove</x-menu.menu-dropdown-title>
                    </x-menu.menu-link>
                    {{-- <form action="{{ route($path . '.destroy', $rowId) }}" class="d-inline" method="POST" x-data
                        @submit.prevent="if (confirm('Are you sure you want to delete this user?')) $el.submit()">
                        @method('DELETE')
                        @csrf

                        <button type="submit" class="btn btn-link">
                            <i class="fa-solid fa-trash"></i>
                            Remove
                        </button>
                    </form> --}}
                </x-menu.menu-item>
            @endif
        </x-menu.menu-dropdown>
    </x-menu.menu-item-dropdown>
</x-menu>
