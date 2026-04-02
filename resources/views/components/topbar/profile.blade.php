<div x-popover class="relative">
    {{-- Profile Toggle --}}
    <button x-ref="button" type="button" x-popover:button
        class="relative inline-flex items-center cursor-pointer leading-none h-10 px-4 border border-transparent font-medium text-2xs outline-none grow rounded-full">
        <div class="size-9 rounded-full border-2 border-success bg-gray-200 flex items-center justify-center shrink-0">
            <x-heroicon-s-user class="size-5 text-gray-500" />
        </div>
    </button>

    {{-- Dropdown --}}
    <div x-popover:panel x-transition.origin.top.right x-cloak
        class="absolute right-0 origin-top-left flex flex-col border border-gray-300 shadow-default bg-white rounded-xl w-screen max-w-[250px] py-2.5">
        {{-- User Info --}}
        <div class="flex items-center px-5 py-1.5 gap-2">
            <div class="size-9 rounded-full border-2 border-success bg-gray-200 flex items-center justify-center shrink-0">
                <x-heroicon-s-user class="size-5 text-gray-500" />
            </div>
            <div class="flex flex-col gap-1.5">
                <span class="text-sm text-gray-800 font-semibold leading-none">
                    {{ Auth::user()->full_name }}
                </span>
                <span class="text-xs text-gray-600 font-medium leading-none">
                    {{ Auth::user()->email }}
                </span>
            </div>
        </div>

        <div class="border-b border-gray-200 my-2.5"></div>

        {{-- Logout --}}
        <div class="flex flex-col px-4 py-1.5">
            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button type="submit"
                    class="btn-light-default btn-light-states w-full justify-center rounded-md px-3 py-2 text-xs font-medium">
                    Log out
                </button>
            </form>
        </div>
    </div>
</div>
