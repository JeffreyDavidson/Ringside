@aware(['component', 'tableName','isTailwind', 'isBootstrap'])

<div class="flex">
    <label
    @class([
        'mb-3 mb-md-0 input-group' => $this->isBootstrap,
        'w-full leading-4 bg-light-active rounded-md border border-solid border-gray-300 flex gap-1.5 items-center text-gray-600 font-medium text-xs h-8 ps-2.5 pe-2.5' => $this->isTailwind,
    ])>
        <i class="ki-filled ki-magnifier"></i>
        <input
            wire:model{{ $this->getSearchOptions() }}="search"
            placeholder="{{ $this->getSearchPlaceholder() }}"
            type="text"
            {{
                $attributes->merge($this->getSearchFieldAttributes())
                ->class([
                    'block w-full border-gray-300 rounded-md shadow-sm transition duration-150 ease-in-out sm:text-sm sm:leading-5 dark:bg-gray-700 dark:text-white dark:border-gray-600 rounded-none rounded-l-md focus:ring-0 focus:border-gray-300' => $this->isTailwind && $this->hasSearch() && $this->getSearchFieldAttributes()['default'] ?? true,
                    'block w-full border-gray-300 rounded-md shadow-sm transition duration-150 ease-in-out sm:text-sm sm:leading-5 dark:bg-gray-700 dark:text-white dark:border-gray-600 rounded-md focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50' => $this->isTailwind && !$this->hasSearch() && $this->getSearchFieldAttributes()['default'] ?? true,
                    'form-control' => $this->isBootstrap && $this->getSearchFieldAttributes()['default'] ?? true,
                ])
                ->except('default')
            }}

        />

        @if ($this->hasSearch())
        <div @class([
                    'd-inline-flex h-100 align-items-center ' => $this->isBootstrap,
                ])>
                <div
                    wire:click="clearSearch"

                    @class([
                            'btn btn-outline-secondary d-inline-flex h-100 align-items-center' => $this->isBootstrap,
                            'inline-flex h-full items-center px-3 text-gray-500 bg-gray-50 rounded-r-md border border-l-0 border-gray-300 cursor-pointer sm:text-sm dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600' => $this->isTailwind,
                        ])
                >
                @if($this->isTailwind)
                <x-heroicon-m-x-mark class='w-4 h-4' />
                @else
                <x-heroicon-m-x-mark class="laravel-livewire-tables-btn-smaller" />
                @endif
                    </div>
            </div>
        @endif
</div>
