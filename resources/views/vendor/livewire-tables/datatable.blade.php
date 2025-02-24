@php($tableName = $this->getTableName)
@php($tableId = $this->getTableId)
@php($primaryKey = $this->getPrimaryKey)
@php($isTailwind = $this->isTailwind)
@php($isBootstrap = $this->isBootstrap)
@php($isBootstrap4 = $this->isBootstrap4)
@php($isBootstrap5 = $this->isBootstrap5)

<div {{ $this->getTopLevelAttributes() }}>
    <x-container-fixed>
        @includeWhen(
            $this->hasConfigurableAreaFor('before-wrapper'),
            $this->getConfigurableAreaFor('before-wrapper'),
            $this->getParametersForConfigurableArea('before-wrapper'))
    </x-container-fixed>
    <x-container-fixed>
        <x-card inGrid>
            <x-livewire-tables::wrapper :component="$this" :tableName="$tableName" :$primaryKey :$isTailwind :$isBootstrap
                :$isBootstrap4 :$isBootstrap5>
                <x-card.header class="flex-wrap gap-2">
                    @if ($this->debugIsEnabled())
                        @include('livewire-tables::includes.debug')
                    @endif
                    @if ($this->offlineIndicatorIsEnabled())
                        @include('livewire-tables::includes.offline')
                    @endif

                    <x-card.title class="font-medium text-sm">
                        @lang('Showing')
                        {{ $this->getRows->total() < $this->perPage ? $this->getRows->total() : $this->perPage }}
                        @lang('of')
                        {{ $this->getRows->total() }}
                        {{ $this->resourceName }}
                    </x-card.title>

                    <div class="flex flex-wrap gap-2 lg:gap-5">
                        @if ($this->hasActions && !$this->showActionsInToolbar)
                            <x-livewire-tables::includes.actions />
                        @endif

                        @if ($this->searchIsEnabled() && $this->searchVisibilityIsEnabled())
                            <x-livewire-tables::tools.toolbar.items.search-field />
                        @endif
                        @if ($this->hasConfigurableAreaFor('before-tools'))
                            @include(
                                $this->getConfigurableAreaFor('before-tools'),
                                $this->getParametersForConfigurableArea('before-tools'))
                        @endif

                        @if ($this->shouldShowTools)
                            <x-livewire-tables::tools>
                                @if ($this->showSortPillsSection)
                                    <x-livewire-tables::tools.sorting-pills />
                                @endif
                                @if ($this->showFilterPillsSection)
                                    <x-livewire-tables::tools.filter-pills />
                                @endif

                                @includeWhen(
                                    $this->hasConfigurableAreaFor('before-toolbar'),
                                    $this->getConfigurableAreaFor('before-toolbar'),
                                    $this->getParametersForConfigurableArea('before-toolbar'))

                                @if ($this->shouldShowToolBar)
                                    <x-livewire-tables::tools.toolbar />
                                @endif
                                @includeWhen(
                                    $this->hasConfigurableAreaFor('after-toolbar'),
                                    $this->getConfigurableAreaFor('after-toolbar'),
                                    $this->getParametersForConfigurableArea('after-toolbar'))

                            </x-livewire-tables::tools>
                        @endif
                    </div>
                </x-card.header>

                <x-card.body>
                    <x-livewire-tables::table>
                        <x-slot name="thead">
                            @if ($this->getCurrentlyReorderingStatus)
                                <x-livewire-tables::table.th.reorder x-cloak x-show="currentlyReorderingStatus" />
                            @endif
                            @if ($this->showBulkActionsSections)
                                <x-livewire-tables::table.th.bulk-actions :displayMinimisedOnReorder="true" />
                            @endif
                            @if ($this->showCollapsingColumnSections)
                                <x-livewire-tables::table.th.collapsed-columns />
                            @endif

                            @foreach ($this->selectedVisibleColumns as $index => $column)
                                <x-livewire-tables::table.th wire:key="{{ $tableName . '-table-head-' . $index }}"
                                    :column="$column" :index="$index" />
                            @endforeach
                        </x-slot>

                        @if ($this->secondaryHeaderIsEnabled() && $this->hasColumnsWithSecondaryHeader())
                            <x-livewire-tables::table.tr.secondary-header />
                        @endif
                        @if ($this->hasDisplayLoadingPlaceholder())
                            <x-livewire-tables::includes.loading colCount="{{ $this->columns->count() + 1 }}" />
                        @endif


                        @if ($this->showBulkActionsSections)
                            <x-livewire-tables::table.tr.bulk-actions :displayMinimisedOnReorder="true" />
                        @endif

                        @forelse ($this->getRows as $rowIndex => $row)
                            <x-livewire-tables::table.tr
                                wire:key="{{ $tableName }}-row-wrap-{{ $row->{$primaryKey} }}" :row="$row"
                                :rowIndex="$rowIndex">
                                @if ($this->getCurrentlyReorderingStatus)
                                    <x-livewire-tables::table.td.reorder x-cloak x-show="currentlyReorderingStatus"
                                        wire:key="{{ $tableName }}-row-reorder-{{ $row->{$primaryKey} }}"
                                        :rowID="$tableName . '-' . $row->{$this->getPrimaryKey()}" :rowIndex="$rowIndex" />
                                @endif
                                @if ($this->showBulkActionsSections)
                                    <x-livewire-tables::table.td.bulk-actions
                                        wire:key="{{ $tableName }}-row-bulk-act-{{ $row->{$primaryKey} }}"
                                        :row="$row" :rowIndex="$rowIndex" />
                                @endif
                                @if ($this->showCollapsingColumnSections)
                                    <x-livewire-tables::table.td.collapsed-columns
                                        wire:key="{{ $tableName }}-row-collapsed-{{ $row->{$primaryKey} }}"
                                        :rowIndex="$rowIndex" />
                                @endif

                                @foreach ($this->selectedVisibleColumns as $colIndex => $column)
                                    <x-livewire-tables::table.td
                                        wire:key="{{ $tableName . '-' . $row->{$primaryKey} . '-datatable-td-' . $column->getSlug() }}"
                                        :column="$column" :colIndex="$colIndex">
                                        @if ($column->isHtml())
                                            {!! $column->renderContents($row) !!}
                                        @else
                                            {{ $column->renderContents($row) }}
                                        @endif
                                    </x-livewire-tables::table.td>
                                @endforeach
                            </x-livewire-tables::table.tr>

                            @if ($this->showCollapsingColumnSections)
                                <x-livewire-tables::table.collapsed-columns :row="$row" :rowIndex="$rowIndex" />
                            @endif
                        @empty
                            <x-livewire-tables::table.empty />
                        @endforelse

                        @if ($this->footerIsEnabled() && $this->hasColumnsWithFooter())
                            <x-slot name="tfoot">
                                @if ($this->useHeaderAsFooterIsEnabled())
                                    <x-livewire-tables::table.tr.secondary-header />
                                @else
                                    <x-livewire-tables::table.tr.footer />
                                @endif
                            </x-slot>
                        @endif
                    </x-livewire-tables::table>
                    <x-card.footer :border="false"
                        class="justify-center md:justify-between flex-col md:flex-row gap-5 text-gray-600 text-2sm font-medium">
                        <div class="flex items-center gap-2 order-2 md:order-1">
                            @if ($this->paginationIsEnabled() && $this->perPageVisibilityIsEnabled())
                                Show <x-livewire-tables::tools.toolbar.items.pagination-dropdown /> per page
                            @endif
                        </div>
                        <div class="flex items-center gap-4 order-1 md:order-2">
                            <x-livewire-tables::pagination />
                        </div>
                    </x-card.footer>
                </x-card.body>

                @includeIf($customView)
            </x-livewire-tables::wrapper>
        </x-card>
    </x-container-fixed>
</div>
