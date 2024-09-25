@props(['icon' => 'ki-element-11'])

<div x-data="{
    open: false,
    toggle() {
        this.open = this.open ? this.close() : true
    },
    close() {
        this.open = false
    }
}" @class(['menu-item', 'menu-item-accordian' => isset($subMenu)])>
    <div @click="toggle()"
        class="menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] pl-[10px] pr-[10px] py-[6px]"
        href="" tabindex="0">
        <span class="menu-icon items-start text-gray-500 dark:text-gray-400 w-[20px]">
            <i class="ki-filled {{ $icon }} text-lg"></i>
        </span>
        <span
            class="menu-title text-sm font-medium text-gray-800 menu-item-active:text-primary menu-link-hover:!text-primary">
            {{ $slot }}
        </span>
        @if (isset($subMenu))
            <span class="menu-arrow text-gray-400 w-[20px] shrink-0 justify-end ml-1 mr-[-10px]">
                <i x-show="!open" class="ki-filled ki-plus text-2xs menu-item-show:hidden"></i>
                <i x-show="open" class="ki-filled ki-minus text-2xs menu-item-show:inline-flex"></i>
            </span>
        @endif
    </div>
    @if (isset($subMenu))
        <div :class="open ? 'show' : 'hidden'" x-show="open" @class([
            'menu-accordion gap-0.5 pl-[10px] relative before:absolute before:left-[20px] before:top-0 before:bottom-0 before:border-l before:border-gray-200',
        ]) style="display: none;">
            {{ $subMenu }}
        </div>
    @endif
</div>
