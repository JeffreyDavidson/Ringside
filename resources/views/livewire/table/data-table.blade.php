@php $stackRowsOnMobile ??= false; @endphp
<div @class(['flex flex-col', 'gap-3' => $tableHeading, 'gap-6' => ! $tableHeading])>
    @if ($beforeWrapperView)
        <x-dynamic-component :component="$beforeWrapperView" />
    @endif

    @if ($tableHeading)
        <h2 class="font-display text-foreground m-0 text-xl leading-none tracking-tight uppercase">
            {{ $tableHeading }}
        </h2>
    @endif

    @if ($historyUnavailable ?? false)
        <p class="text-muted-foreground text-sm">{{ __('matches.history.unavailable') }}</p>
    @elseif ($tableHeading && $rows->isEmpty() && ! $hasActiveFilters)
        <p
            class="border-border bg-surface-header text-muted-foreground m-0 border px-4 py-4 text-sm"
            data-test="{{ $this->resourceName }}-empty-state"
        >
            {{ __('core.no_related_records', ['resource' => Str::lower($tableHeading)]) }}
        </p>
    @else
        <div class="border-border bg-surface-header border">
            <x-tables.toolbar
                :id="$this->resourceName.'-search'"
                model="search"
                :value="$search"
                :label="$searchPlaceholder"
                :placeholder="$searchPlaceholder"
                :clear-label="__('core.clear_search')"
            >
                <x-tables.meta-data />

                @if (count($filters) > 0)
                    @foreach ($filters as $filter)
                        @if ($filter instanceof \App\Livewire\Table\Filters\SelectFilter)
                            <label
                                class="sr-only"
                                for="table-filter-{{ $filter->getKey() }}"
                            >{{ $filter->getName() }}</label>
                            <select
                                id="table-filter-{{ $filter->getKey() }}"
                                wire:model.live="filterValues.{{ $filter->getKey() }}"
                                class="border-outline bg-surface text-foreground focus-visible:outline-foreground min-h-11 appearance-none border px-3 text-sm focus-visible:outline-2"
                            >
                                @foreach ($filter->getOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        @endif
                    @endforeach
                @endif
            </x-tables.toolbar>

            @if ($rows->isNotEmpty())
                <p
                    id="{{ $this->resourceName }}-table-scroll-hint"
                    @class(['border-border text-muted-foreground items-center justify-end gap-2 border-b px-4 py-2 text-xs md:hidden', 'hidden' => $stackRowsOnMobile, 'flex' => ! $stackRowsOnMobile])
                >
                    {{ __('core.table_scroll_hint') }}
                    <x-heroicon-o-arrow-right class="size-4 shrink-0" aria-hidden="true" />
                </p>

                <div
                    data-test="resource-table-scroll"
                    role="region"
                    tabindex="0"
                    aria-label="{{ __('core.table_results_region') }}"
                    aria-describedby="{{ $this->resourceName }}-table-scroll-hint"
                    @class(['focus-visible:outline-foreground focus-visible:outline-2 focus-visible:outline-offset-[-2px]', 'overflow-x-auto' => ! $stackRowsOnMobile, 'md:overflow-x-auto' => $stackRowsOnMobile])
                >
                    <table @class(['w-full table-auto border-collapse text-left text-sm', 'min-w-[40rem]' => ! $stackRowsOnMobile, 'block md:table' => $stackRowsOnMobile])>
                        <thead @class(['hidden md:table-header-group' => $stackRowsOnMobile])>
                            <tr>
                                @foreach ($columns as $column)
                                    @php
                                        $columnSort = match (true) {
                                            ! $column->isSortable() => null,
                                            $sortField !== $column->getField() => 'none',
                                            $sortDirection === 'asc' => 'ascending',
                                            default => 'descending',
                                        };
                                    @endphp
                                    <th
                                        scope="col"
                                        @if ($columnSort) aria-sort="{{ $columnSort }}" @endif
                                        class="border-border bg-surface-panel text-muted-foreground px-4 py-3 align-middle text-xs font-semibold tracking-[0.08em] uppercase
                                {{ !$loop->last ? 'border-e' : '' }}
                                {{ $column->getTitle() === __('core.actions') ? 'w-[60px]' : '' }}"
                                    >
                                        @if ($column->isSortable())
                                            <button
                                                type="button"
                                                wire:click="sort('{{ $column->getField() }}')"
                                                class="text-muted-foreground hover:text-foreground focus-visible:outline-foreground flex items-center gap-1 focus-visible:outline-2 focus-visible:outline-offset-2"
                                            >
                                                {{ $column->getTitle() }}
                                                @if ($columnSort === 'ascending')
                                                    <x-heroicon-s-chevron-up class="size-3" aria-hidden="true" />
                                                @elseif ($columnSort === 'descending')
                                                    <x-heroicon-s-chevron-down class="size-3" aria-hidden="true" />
                                                @endif
                                            </button>
                                        @else
                                            {{ $column->getTitle() }}
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody @class(['block md:table-row-group' => $stackRowsOnMobile])>
                            @foreach ($rows as $row)
                                <tr
                                    wire:key="row-{{ $row->{$this->primaryKey ?? 'id'} }}"
                                    @class(['border-border hover:bg-surface-hover border-b transition-colors', 'block md:table-row' => $stackRowsOnMobile])
                                >
                                    @foreach ($columns as $column)
                                        <td @class(['text-foreground px-4 py-4', 'border-border border-e' => ! $loop->last && ! $stackRowsOnMobile, 'block md:table-cell' => $stackRowsOnMobile])>
                                            @if ($stackRowsOnMobile)
                                                <span class="text-muted-foreground mb-1 block text-xs font-semibold md:hidden">{{ $column->getTitle() }}</span>
                                            @endif
                                            @if ($column->isHtml())
                                                {{ $column->resolveHtmlValue($row) }}
                                            @else
                                                {{ $column->resolveValue($row) }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif ($hasActiveFilters)
                <x-tables.empty-state
                    :title="__('core.no_results_title')"
                    :description="__('core.no_results_description')"
                    icon="heroicon-o-magnifying-glass"
                    :data-test="$this->resourceName.'-empty-state'"
                    class="border-b"
                >
                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="text-foreground hover:bg-surface-hover focus-visible:outline-foreground border-border mt-2 inline-flex min-h-11 items-center border px-4 text-sm focus-visible:outline-2 focus-visible:outline-offset-2"
                    >
                        {{ $hasAppliedFilters ? __('core.clear_filters') : __('core.clear_search') }}
                    </button>
                </x-tables.empty-state>
            @else
                <x-tables.empty-state
                    :title="$emptyStateTitle ?? __('core.no_records_found')"
                    :description="$emptyStateDescription ?? __('core.no_records_description')"
                    :icon="$emptyStateIcon"
                    :data-test="$this->resourceName.'-empty-state'"
                    class="border-b"
                />
            @endif

            <x-tables.footer
                :paginator="$rows"
                :per-page-options="$perPageOptions"
                :per-page-id="$this->resourceName.'-per-page'"
                :results-label="__('core.table_results', ['first' => $rows->firstItem(), 'last' => $rows->lastItem(), 'total' => $rows->total(), 'resource' => $this->resourceName])"
                :per-page-label="__('core.rows_per_page')"
                :pagination-label="__('core.table_pages')"
                :previous-page-label="__('core.previous_page')"
                :next-page-label="__('core.next_page')"
                :page-label="__('core.page', ['current' => $rows->currentPage(), 'last' => $rows->lastPage()])"
            />
        </div>
    @endif

    <div
        wire:loading.delay
        role="status"
        data-test="table-updating-status"
        class="border-border bg-surface-header text-muted-foreground fixed end-4 bottom-4 z-50 border px-4 py-3 text-sm shadow-xl"
    >
        {{ __('core.updating') }}
    </div>
</div>
