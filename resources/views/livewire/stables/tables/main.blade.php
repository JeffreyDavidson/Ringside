<div class="flex min-w-0 flex-col gap-6">
    <x-stables.index.table-pre />

    <section
        class="border-ringside-line bg-ringside-surface-header min-w-0 border"
        aria-label="{{ __('stables.index_title') }}"
    >
        <x-tables.status-filters
            :metadata="$this->metadata"
            :label="__('stables.filter_status')"
            :all-label="__('stables.all')"
            :selected="$filterValues['status'] ?? ''"
            status-id="stables-status"
            test-id="stables-status-filters"
        />

        <div class="flex flex-wrap items-center justify-between gap-3 p-4">
            <x-tables.search-field
                id="stables-search"
                model="search"
                :value="$search"
                :label="__('stables.search')"
                :placeholder="__('stables.search')"
                :clear-label="__('stables.clear_search')"
            />
            <details class="group relative w-full sm:w-auto">
                <summary class="border-ringside-line text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink focus-visible:outline-ringside-ink inline-flex min-h-11 cursor-pointer list-none items-center gap-2 border px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2">
                    {{ __('stables.filters') }}
                    <x-heroicon-o-chevron-down
                        class="size-4 transition-transform group-open:rotate-180"
                        aria-hidden="true"
                    />
                </summary>
                <div class="border-ringside-line bg-ringside-surface-header mt-2 grid w-full gap-4 border p-4 shadow-xl sm:absolute sm:end-0 sm:top-full sm:z-20 sm:mt-2 sm:w-[22rem]">
                    <fieldset class="grid gap-2">
                        <legend class="text-ringside-muted text-xs font-medium">
                            {{ __('stables.activation_date') }}
                        </legend>
                        <div data-test="stables-date-range-grid" class="grid grid-cols-1 gap-3 min-[380px]:grid-cols-2">
                            <div class="grid gap-1">
                                <label
                                    for="stables-date-from"
                                    class="text-ringside-muted text-xs"
                                >{{ __('stables.from') }}</label>
                                <input
                                    id="stables-date-from"
                                    type="date"
                                    wire:model.live="filterValues.activation_date.minDate"
                                    class="border-ringside-line bg-ringside-surface text-ringside-ink focus-visible:outline-ringside-ink min-h-11 min-w-0 border px-2 text-sm focus-visible:outline-2"
                                />
                            </div>
                            <div class="grid gap-1">
                                <label
                                    for="stables-date-to"
                                    class="text-ringside-muted text-xs"
                                >{{ __('stables.to') }}</label>
                                <input
                                    id="stables-date-to"
                                    type="date"
                                    wire:model.live="filterValues.activation_date.maxDate"
                                    class="border-ringside-line bg-ringside-surface text-ringside-ink focus-visible:outline-ringside-ink min-h-11 min-w-0 border px-2 text-sm focus-visible:outline-2"
                                />
                            </div>
                        </div>
                    </fieldset>
                    <button
                        type="button"
                        wire:click="clearFilters"
                        @disabled($search === '' && ($filterValues['status'] ?? '') === '' && empty($filterValues['activation_date']['minDate'] ?? null) && empty($filterValues['activation_date']['maxDate'] ?? null))
                        class="text-ringside-ink border-ringside-line hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink min-h-11 cursor-pointer justify-self-start border px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-default disabled:opacity-50"
                    >
                        {{ __('stables.clear_filters') }}
                    </button>
                </div>
            </details>
            <span wire:loading.delay role="status" class="text-ringside-muted text-xs">
                {{ __('stables.updating') }}
            </span>
        </div>

        @if ($rows->isNotEmpty())
            <table class="w-full table-fixed border-collapse text-left text-sm" data-test="stables-table">
                <caption class="sr-only">
                    {{ __('stables.index_title') }}
                </caption>
                <thead class="border-ringside-line bg-ringside-surface text-ringside-muted border-y text-xs">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium sm:px-5">{{ __('stables.name') }}</th>
                        <th scope="col" class="hidden w-48 px-4 py-3 font-medium sm:table-cell">
                            {{ __('core.status') }}
                        </th>
                        <th scope="col" class="hidden w-40 px-4 py-3 font-medium lg:table-cell">
                            {{ __('activations.started_at') }}
                        </th>
                        <th scope="col" class="w-16 px-2 py-3">
                            <span class="sr-only">{{ __('core.actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-ringside-line divide-y">
                    @foreach ($rows as $row)
                        <tr wire:key="row-{{ $row->id }}" class="hover:bg-ringside-surface-hover/40 transition-colors">
                            <td class="px-4 py-4 align-top sm:px-5">
                                <a
                                    href="{{ route('stables.show', $row) }}"
                                    class="text-ringside-ink focus-visible:outline-ringside-ink font-semibold wrap-break-word underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
                                >{{ $row->name }}</a>
                                <p class="text-ringside-muted m-0 mt-1 text-xs leading-5 wrap-break-word">
                                    {{ trans_choice('stables.members_count', $row->currentWrestlers->count() + $row->currentTagTeams->count(), ['count' => $row->currentWrestlers->count() + $row->currentTagTeams->count()]) }}
                                </p>
                                <div class="mt-2 sm:hidden"><x-tables.stable-status :status="$row->status" /></div>
                                <p class="text-ringside-muted m-0 mt-2 text-xs leading-5 tabular-nums lg:hidden">
                                    {{ $row->firstActivityPeriod?->started_at?->format('M j, Y') ?? '—' }}
                                </p>
                            </td>
                            <td class="hidden px-4 py-4 align-top sm:table-cell">
                                <x-tables.stable-status :status="$row->status" />
                            </td>
                            <td class="text-ringside-muted hidden px-4 py-4 align-top text-xs leading-5 tabular-nums lg:table-cell">
                                {{ $row->firstActivityPeriod?->started_at?->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="px-2 py-3 align-top"><x-tables.columns.stable-actions :stable="$row" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            @if ($search !== '' || ($filterValues['status'] ?? '') !== '' || ! empty($filterValues['activation_date']['minDate'] ?? null) || ! empty($filterValues['activation_date']['maxDate'] ?? null))
                <x-tables.empty-state
                    :title="__('stables.no_results_title')"
                    :description="__('stables.no_results_description')"
                    icon="heroicon-o-user-group"
                    data-test="stables-empty-state"
                >
                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="text-ringside-ink border-ringside-line hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink mt-2 min-h-11 cursor-pointer border px-4 text-sm focus-visible:outline-2 focus-visible:outline-offset-2"
                    >
                        {{ __('stables.clear_filters') }}
                    </button>
                </x-tables.empty-state>
            @else
                @can('create', \App\Models\Roster\Stables\Stable::class)
                    <x-tables.empty-state
                        :title="__('stables.empty_title')"
                        :description="__('stables.empty_description')"
                        description-class="text-xs whitespace-nowrap sm:text-sm"
                        icon="heroicon-o-user-group"
                        data-test="stables-empty-state"
                    />
                @else
                    <x-tables.empty-state
                        :title="__('stables.empty_title')"
                        :description="__('stables.empty_read_only_description')"
                        icon="heroicon-o-user-group"
                        data-test="stables-empty-state"
                    />
                @endcan
            @endif
        @endif

        <x-tables.footer
            :paginator="$rows"
            :per-page-options="$perPageOptions"
            per-page-id="stables-per-page"
            per-page-model="perPage"
            :results-label="__('stables.results', ['first' => $rows->firstItem(), 'last' => $rows->lastItem(), 'total' => $rows->total()])"
            :per-page-label="__('stables.per_page')"
            :pagination-label="__('stables.pagination')"
            :previous-page-label="__('stables.previous_page')"
            :next-page-label="__('stables.next_page')"
            :page-label="__('stables.page', ['current' => $rows->currentPage(), 'last' => $rows->lastPage()])"
        />
    </section>
</div>
