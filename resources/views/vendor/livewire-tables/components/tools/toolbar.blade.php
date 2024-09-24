@aware(['component', 'tableName', 'isTailwind', 'isBootstrap'])
@props([])

<div class="card-header flex-wrap gap-2">
    <h3 class="card-title font-medium text-sm">
        Showing 10 of 49,053 users
    </h3>
    <div class="flex flex-wrap gap-2 lg:gap-5">
        @if ($this->reorderIsEnabled())
            <x-livewire-tables::tools.toolbar.items.reorder-buttons />
        @endif

        @if ($this->searchIsEnabled() && $this->searchVisibilityIsEnabled())
            <x-livewire-tables::tools.toolbar.items.search-field />
        @endif

        @if ($this->filtersAreEnabled() && $this->filtersVisibilityIsEnabled() && $this->hasVisibleFilters())
            <x-livewire-tables::tools.toolbar.items.filter-button />
        @endif

        @if ($this->hasActions && $this->showActionsInToolbar && $this->getActionsPosition == 'left')
            <x-livewire-tables::includes.actions />
        @endif

        @if ($this->hasConfigurableAreaFor('toolbar-left-end'))
            <div x-cloak x-show="!currentlyReorderingStatus" @class([
                'mb-3 mb-md-0 input-group' => $this->isBootstrap,
                'flex rounded-md shadow-sm' => $this->isTailwind,
            ])>
                @include(
                    $this->getConfigurableAreaFor('toolbar-left-end'),
                    $this->getParametersForConfigurableArea('toolbar-left-end'))
            </div>
        @endif
    </div>

    <div x-cloak x-show="!currentlyReorderingStatus" @class([
        'd-md-flex' => $this->isBootstrap,
        'md:flex md:items-center space-y-4 md:space-y-0 md:space-x-2' =>
            $this->isTailwind,
    ])>
        @if ($this->hasConfigurableAreaFor('toolbar-right-start'))
            @include(
                $this->getConfigurableAreaFor('toolbar-right-start'),
                $this->getParametersForConfigurableArea('toolbar-right-start'))
        @endif

        @if ($this->hasActions && $this->showActionsInToolbar && $this->getActionsPosition == 'right')
            <x-livewire-tables::includes.actions />
        @endif

        @if ($this->showBulkActionsDropdownAlpine() && $this->shouldAlwaysHideBulkActionsDropdownOption != true)
            <x-livewire-tables::tools.toolbar.items.bulk-actions />
        @endif

        @if ($this->columnSelectIsEnabled())
            <x-livewire-tables::tools.toolbar.items.column-select />
        @endif

        @if ($this->hasConfigurableAreaFor('toolbar-right-end'))
            @include(
                $this->getConfigurableAreaFor('toolbar-right-end'),
                $this->getParametersForConfigurableArea('toolbar-right-end'))
        @endif
    </div>
</div>
@if (
    $this->filtersAreEnabled() &&
        $this->filtersVisibilityIsEnabled() &&
        $this->hasVisibleFilters() &&
        $this->isFilterLayoutSlideDown())
    <x-livewire-tables::tools.toolbar.items.filter-slidedown />
@endif
</div>
