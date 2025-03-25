@aware(['isTailwind', 'isBootstrap'])

<div class="flex">
    <div @class([
        'mb-3 mb-md-0 input-group' => $isBootstrap,
        'rounded-md shadow-sm' => $isTailwind,
        'flex' => !$this->hasSearchIcon,
        'flex gap-1.5 items-center appearance-none outline-none font-md text-xs h-8 ps-2.5 pe-2.5 w-full leading-none bg-light-active rounded-md border border-solid border-gray-300 placeholder:text-gray-500 placeholder:text-xs hover:border-gray-400 has-[:focus]:border-primary has-[:focus]:text-gray-700' =>
            $this->hasSearchIcon,
    ])>

        @if ($this->hasSearchIcon)
            <x-livewire-tables::tools.toolbar.items.search.icon :searchIcon="$this->getSearchIcon" :searchIconClasses="$this->getSearchIconClasses" :searchIconOtherAttributes="$this->getSearchIconOtherAttributes" />
        @endif

        <x-livewire-tables::tools.toolbar.items.search.input />

        @if ($this->hasSearch)
            <x-livewire-tables::tools.toolbar.items.search.remove />
        @endif
    </div>
</div>
