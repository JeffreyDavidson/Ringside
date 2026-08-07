<div x-popover class="relative">
    {{-- Profile Toggle --}}
    <button
        x-ref="button"
        type="button"
        x-popover:button
        class="text-2xs relative inline-flex h-10 grow cursor-pointer items-center rounded-full border border-transparent px-4 leading-none font-medium outline-none"
    >
        <div class="border-success flex size-9 shrink-0 items-center justify-center rounded-full border-2 bg-gray-200">
            <x-heroicon-s-user class="size-5 text-gray-500" />
        </div>
    </button>

    {{-- Dropdown --}}
    <div
        x-popover:panel
        x-transition.origin.top.right
        x-cloak
        class="shadow-default absolute right-0 flex w-screen max-w-[250px] origin-top-left flex-col rounded-xl border border-gray-300 bg-white py-2.5"
    >
        {{-- User Info --}}
        <div class="flex items-center gap-2 px-5 py-1.5">
            <div class="border-success flex size-9 shrink-0 items-center justify-center rounded-full border-2 bg-gray-200">
                <x-heroicon-s-user class="size-5 text-gray-500" />
            </div>
            <div class="flex flex-col gap-1.5">
                <span class="text-sm leading-none font-semibold text-gray-800"> {{ Auth::user()->full_name }} </span>
                <span class="text-xs leading-none font-medium text-gray-600"> {{ Auth::user()->email }} </span>
            </div>
        </div>

        <div class="my-2.5 border-b border-gray-200"></div>

        {{-- Logout --}}
        <div class="flex flex-col px-4 py-1.5">
            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button
                    type="submit"
                    class="btn-light-default btn-light-states w-full justify-center rounded-md px-3 py-2 text-xs font-medium"
                >
                    Log out
                </button>
            </form>
        </div>
    </div>
</div>
