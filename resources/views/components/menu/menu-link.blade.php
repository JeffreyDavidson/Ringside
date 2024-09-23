<div class="menu-item">
    <a class="menu-link border border-transparent items-center grow menu-item-active:bg-secondary-active dark:menu-item-active:bg-coal-300 dark:menu-item-active:border-gray-100 menu-item-active:rounded-lg hover:bg-secondary-active dark:hover:bg-coal-300 dark:hover:border-gray-100 hover:rounded-lg gap-[14px] pl-[10px] pr-[10px] py-[8px]"
        {{ $attributes }} tabindex="0">
        @isset($icon)
            <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
                <i class="ki-filled {{ $icon }} text-lg"></i>
            </span>
        @else
            <span
                class="menu-bullet flex w-[6px] relative before:absolute before:top-0 before:size-[6px] before:rounded-full before:-translate-x-1/2 before:-translate-y-1/2 menu-item-active:before:bg-primary menu-item-hover:before:bg-primary">
            </span>
        @endisset
        <span
            class="menu-title text-2sm font-normal text-gray-800 menu-item-active:text-primary menu-item-active:font-semibold menu-link-hover:!text-primary">
            {{ $slot }}
        </span>
    </a>
</div>
