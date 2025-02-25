<!-- Profile -->
<!-- Popover -->
<div x-popover class="relative">
    <!-- Menu Toggle -->
    <button x-ref="button" type="button" x-popover:button
        class="relative inline-flex items-center cursor-pointer leading-none h-10 ps-4 pe-4 border border-solid border-transparent font-medium text-2xs outline-none grow rounded-full">
        <img class="size-9 rounded-full border-2 border-success shrink-0"
            src="{{ Vite::image('avatars/' . Auth::user()->getAvatar()) }}">
        </img>
    </button>
    <!-- Menu Dropdown -->
    <div x-popover:panel x-transition.origin.top.right x-cloak
        class="absolute right-0 origin-top-left p-0 m-0 flex flex-col border border-solid border-gray-300 shadow-[0_7px_18px_0px_rgba(0,0,0,0.09)] bg-white rounded-xl w-screen max-w-[250px] py-2.5">
        <div class="flex items-center justify-between px-5 py-1.5 gap-1.5">
            <div class="flex items-center gap-2">
                <img alt="" class="size-9 rounded-full border-2 border-success"
                    src="{{ Vite::image('avatars/' . Auth::user()->getAvatar()) }}">
                <div class="flex flex-col gap-1.5">
                    <span class="text-sm text-gray-800 font-semibold leading-none">
                        {{ Auth::user()->full_name }}
                    </span>
                    <span class="text-xs text-gray-600 font-medium leading-none">
                        {{ Auth::user()->email }}
                    </span>
                </div>
                </img>
            </div>
        </div>
        <div class="border-b border-dropdown my-2.5"></div>
        <div class="flex flex-col p-0 m-0">
            <div class="group flex items-center grow ms-2.5 me-2.5 p-2.5 rounded-md cursor-pointer hover:bg-gray-100">
                <span class="flex items-center shrink-0 me-2.5">
                    <i class="ki-filled ki-icon text-lg text-gray-500 group-hover:text-primary"></i>
                </span>
                <span class="flex items-center grow leading-4.25 font-medium text-2sm font-gray-800">
                    Language
                </span>
                <div
                    class="flex items-center gap-1.5 rounded-md border border-gray-300 text-gray-600 p-1.5 text-2xs font-medium shrink-0">
                    English
                    <img alt="" class="inline-block size-3.5 rounded-full"
                        src="{{ Vite::image('flags/united-states.svg') }}">
                </div>
            </div>
        </div>
        <div class="border-b border-dropdown my-2.5"></div>
        <div class="flex flex-col">
            <!-- Menu Item -->
            <div class="flex flex-col m-0 px-4 py-1.5">
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <x-buttons.light size="sm" class="justify-center w-full">Log out</x-buttons.light>
                </form>
            </div>
        </div>
    </div>
</div>
