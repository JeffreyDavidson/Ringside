<div class="flex flex-col gap-6">
    @if ($beforeWrapperView)
        <x-dynamic-component :component="$beforeWrapperView" />
    @endif

    <div class="border-ringside-line bg-ringside-surface-header border">
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
                            class="border-ringside-line bg-ringside-surface text-ringside-muted focus:border-ringside-ink min-h-11 appearance-none border px-3 text-sm focus:ring-0"
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
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] table-auto border-collapse text-left text-sm">
                    <thead>
                        <tr>
                            @foreach ($columns as $column)
                                <th
                                    class="border-ringside-line bg-ringside-surface-panel text-ringside-muted px-4 py-3 align-middle text-xs font-semibold tracking-[0.08em] uppercase
                                {{ !$loop->last ? 'border-e' : '' }}
                                {{ $column->getTitle() === __('core.actions') ? 'w-[60px]' : '' }}"
                                >
                                    @if ($column->isSortable())
                                        <button
                                            wire:click="sort('{{ $column->getField() }}')"
                                            class="text-ringside-muted hover:text-ringside-ink flex items-center gap-1"
                                        >
                                            {{ $column->getTitle() }}
                                            @if ($sortField === $column->getField())
                                                @if ($sortDirection === 'asc')
                                                    <x-heroicon-s-chevron-up class="size-3" />
                                                @else
                                                    <x-heroicon-s-chevron-down class="size-3" />
                                                @endif
                                            @endif
                                        </button>
                                    @else
                                        {{ $column->getTitle() }}
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr
                                wire:key="row-{{ $row->{$this->primaryKey ?? 'id'} }}"
                                class="border-ringside-line hover:bg-ringside-surface-hover border-b transition-colors"
                            >
                                @foreach ($columns as $column)
                                    <td
                                        class="text-ringside-ink px-4 py-4
                                    {{ !$loop->last ? 'border-ringside-line border-e' : '' }}"
                                    >
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
                    class="text-ringside-ink hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink border-ringside-line mt-2 inline-flex min-h-11 items-center border px-4 text-sm focus-visible:outline-2 focus-visible:outline-offset-2"
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

    <div
        wire:loading.delay
        class="border-ringside-line bg-ringside-surface-header text-ringside-muted fixed end-4 bottom-4 z-50 border px-4 py-3 text-sm shadow-xl"
    >
        Updating…
    </div>
</div>
