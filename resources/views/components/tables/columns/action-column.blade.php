<x-menu>
    <x-menu.menu-item-dropdown>
        <x-menu.menu-toggle class="inline-flex items-center cursor-pointer leading-4 rounded-md border border-solid border-transparent outline-none h-8 ps-3 pe-3 font-medium text-xs justify-center shrink-0 p-0 gap-0 w-8 bg-transparent text-gray-700"/>
        <x-menu.menu-dropdown class="py-2.5 w-full max-w-[175px]">
            @if ($links['view'] ?? true)
                <x-menu.menu-item>
                    <a href="{{ route($path . '.show', $rowId) }}">
                        <span class="menu-icon">
                            <i class="ki-filled ki-search-list"></i>
                        </span>
                        <span class="menu-title">View</span>
                    </a>
                </x-menu.menu-item>
            @endif

            <x-menu.menu-item-separator/>

            @if ($links['edit'] ?? true)
                <x-menu.menu-item>
                    <a href="{{ route($path . '.edit', $rowId) }}">
                        <span class="menu-icon">
                            <i class="ki-filled ki-pencil"></i>
                        </span>
                        <span class="menu-title">Edit</span>
                    </a>
                </x-menu.menu-item>
            @endif

            @if ($links['delete'] ?? true)
                <x-menu.menu-item>
                    <form action="{{ route($path . '.destroy', $rowId) }}" class="d-inline" method="POST" x-data
                        @submit.prevent="if (confirm('Are you sure you want to delete this user?')) $el.submit()">
                        @method('DELETE')
                        @csrf

                        <button type="submit" class="btn btn-link">
                            <i class="fa-solid fa-trash"></i>
                            Remove
                        </button>
                    </form>
                </x-menu.menu-item>
            @endif
        </x-menu.menu-dropdown>
    </x-menu.menu-item-dropdown>
</x-menu>
