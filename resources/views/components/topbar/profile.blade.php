<div class="flex">
    <div class="flex flex-col m-0 p-0">
        <div class="inline-flex items-center cursor-pointer leading-none h-10 ps-4 pe-4 border border-solid border-transparent font-medium text-2xs outline-none grow rounded-full">
            <img class="size-9 rounded-full border-2 border-success shrink-0"
                src="{{ Vite::image('avatars/blank.png') }}">
            </img>
        </div>
        <div class="menu-dropdown m-0 py-2.5 border-gray-300 w-screen max-w-[250px] hidden border border-solid bg-white">
            <div class="flex items-center justify-between px-5 py-1.5 gap-1.5">
                <div class="flex items-center gap-2">
                    <img alt="" class="size-9 rounded-full border-2 border-success"
                        src="{{ Vite::image('avatars/blank.png') }}">
                    <div class="flex flex-col gap-1.5">
                        <span class="text-sm text-gray-800 font-semibold leading-none">
                            Cody Fisher
                        </span>
                        <span class="text-xs text-gray-600 font-medium leading-none">
                            c.fisher@gmail.com
                        </span>
                    </div>
                    </img>
                </div>
            </div>
            <div class="menu-separator"></div>
            <div class="flex flex-col">
                <div class="menu-item">
                    <div class="menu-link">
                        <span class="menu-icon">
                            <i class="ki-filled ki-icon"></i>
                        </span>
                        <span class="menu-title">
                            Language
                        </span>
                        <div
                            class="flex items-center gap-1.5 rounded-md border border-gray-300 text-gray-600 p-1.5 text-2xs font-medium shrink-0">
                            English
                            <img alt="" class="inline-block size-3.5 rounded-full"
                                src="{{ Vite::image('flags/united-states.svg') }}" />
                        </div>
                    </div>
                    <div class="menu-dropdown menu-default light:border-gray-300 w-full max-w-[170px]">
                        <div class="menu-item active">
                            <a class="menu-link h-10" href="#">
                                <span class="menu-icon">
                                    <img alt="" class="inline-block size-4 rounded-full"
                                        src="{{ Vite::image('flags/united-states.svg') }}" />
                                </span>
                                <span class="menu-title">
                                    English
                                </span>
                                <span class="menu-badge">
                                    <i class="ki-solid ki-check-circle text-success text-base"></i>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="menu-separator"></div>
            <div class="flex flex-col">
                <div class="menu-item px-4 py-1.5">
                    <a class="btn btn-sm btn-light justify-center"
                        href="html/demo1/authentication/classic/sign-in.html">
                        Log out
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
