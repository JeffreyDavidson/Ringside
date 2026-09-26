<div class="flex min-w-0 flex-col gap-6">
    @if ($beforeWrapperView)
        <x-dynamic-component :component="$beforeWrapperView" />
    @endif

    <section
        class="border-ringside-line bg-ringside-surface-header min-w-0 border"
        aria-label="{{ __('referees.index_title') }}"
    >
        <x-tables.status-filters
            :metadata="$this->metadata"
            :label="__('referees.filter_status')"
            :all-label="__('referees.all')"
            :selected="$filterValues['status'] ?? ''"
            status-id="referees-status"
            test-id="referees-status-filters"
        />

        <div class="flex flex-wrap items-center justify-between gap-3 p-4">
            <x-tables.search-field
                id="referees-search"
                model="search"
                :value="$search"
                :label="__('referees.search')"
                :placeholder="__('referees.search')"
                :clear-label="__('referees.clear_search')"
            />
            <details class="group relative w-full sm:w-auto">
                <summary class="border-ringside-line text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink focus-visible:outline-ringside-ink inline-flex min-h-11 cursor-pointer list-none items-center gap-2 border px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2">
                    {{ __('referees.filters') }}
                    <x-heroicon-o-chevron-down
                        class="size-4 transition-transform group-open:rotate-180"
                        aria-hidden="true"
                    />
                </summary>
                <div class="border-ringside-line bg-ringside-surface-header mt-2 grid w-full gap-4 border p-4 shadow-xl sm:absolute sm:end-0 sm:top-full sm:z-20 sm:mt-2 sm:w-[22rem]">
                    <fieldset class="grid gap-2">
                        <legend class="text-ringside-muted text-xs font-medium">
                            {{ __('referees.employment_date') }}
                        </legend>
                        <div
                            data-test="referees-date-range-grid"
                            class="grid grid-cols-1 gap-3 min-[380px]:grid-cols-2"
                        >
                            <div class="grid gap-1">
                                <label for="referees-date-from" class="text-ringside-muted text-xs">
                                    {{ __('referees.from') }}
                                </label>
                                <input
                                    id="referees-date-from"
                                    type="date"
                                    wire:model.live="filterValues.employment_date.minDate"
                                    class="border-ringside-line bg-ringside-surface text-ringside-ink focus-visible:outline-ringside-ink min-h-11 min-w-0 border px-2 text-sm focus-visible:outline-2"
                                />
                            </div>
                            <div class="grid gap-1">
                                <label for="referees-date-to" class="text-ringside-muted text-xs">
                                    {{ __('referees.to') }}
                                </label>
                                <input
                                    id="referees-date-to"
                                    type="date"
                                    wire:model.live="filterValues.employment_date.maxDate"
                                    class="border-ringside-line bg-ringside-surface text-ringside-ink focus-visible:outline-ringside-ink min-h-11 min-w-0 border px-2 text-sm focus-visible:outline-2"
                                />
                            </div>
                        </div>
                    </fieldset>
                    <button
                        type="button"
                        wire:click="clearFilters"
                        @disabled($search === '' && ($filterValues['status'] ?? '') === '' && empty($filterValues['employment_date']['minDate'] ?? null) && empty($filterValues['employment_date']['maxDate'] ?? null))
                        class="text-ringside-ink border-ringside-line hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink min-h-11 cursor-pointer justify-self-start border px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-default disabled:opacity-50"
                    >
                        {{ __('referees.clear_filters') }}
                    </button>
                </div>
            </details>
            <span wire:loading.delay role="status" class="text-ringside-muted text-xs">
                {{ __('referees.updating') }}
            </span>
        </div>

        @if ($rows->isNotEmpty())
            <table class="w-full table-fixed border-collapse text-left text-sm" data-test="referees-table">
                <caption class="sr-only">
                    {{ __('referees.index_title') }}
                </caption>
                <thead class="border-ringside-line bg-ringside-surface text-ringside-muted border-y text-xs">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium sm:px-5">{{ __('referees.name') }}</th>
                        <th scope="col" class="hidden w-48 px-4 py-3 font-medium sm:table-cell">
                            {{ __('core.status') }}
                        </th>
                        <th scope="col" class="hidden w-40 px-4 py-3 font-medium lg:table-cell">
                            {{ __('employments.started_at') }}
                        </th>
                        <th scope="col" class="w-16 px-2 py-3">
                            <span class="sr-only">{{ __('core.actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-ringside-line divide-y">
                    @foreach ($rows as $row)
                        <tr
                            wire:key="referee-{{ $row->id }}"
                            class="hover:bg-ringside-surface-hover/40 transition-colors"
                        >
                            <td class="px-4 py-4 align-top sm:px-5">
                                <a
                                    href="{{ route('referees.show', $row) }}"
                                    class="text-ringside-ink focus-visible:outline-ringside-ink font-semibold wrap-break-word underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
                                >{{ $row->full_name }}</a>
                                <div class="mt-2 sm:hidden"><x-tables.status :status="$row->status" /></div>
                                <p class="text-ringside-muted m-0 mt-2 text-xs leading-5 tabular-nums lg:hidden">
                                    {{ $row->firstEmployment?->started_at?->format('M j, Y') ?? '—' }}
                                </p>
                            </td>
                            <td class="hidden px-4 py-4 align-top sm:table-cell">
                                <x-tables.status :status="$row->status" />
                            </td>
                            <td class="text-ringside-muted hidden px-4 py-4 align-top text-xs leading-5 tabular-nums lg:table-cell">
                                {{ $row->firstEmployment?->started_at?->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="px-2 py-3 align-top"><x-tables.columns.referee-actions :referee="$row" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            @if ($search !== '' || ($filterValues['status'] ?? '') !== '' || ! empty($filterValues['employment_date']['minDate'] ?? null) || ! empty($filterValues['employment_date']['maxDate'] ?? null))
                <x-tables.empty-state
                    :title="__('referees.no_results_title')"
                    :description="__('referees.no_results_description')"
                    icon="heroicon-o-hand-raised"
                    data-test="referees-empty-state"
                >
                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="text-ringside-ink border-ringside-line hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink mt-2 min-h-11 cursor-pointer border px-4 text-sm focus-visible:outline-2 focus-visible:outline-offset-2"
                    >
                        {{ __('referees.clear_filters') }}
                    </button>
                </x-tables.empty-state>
            @else
                @can('create', \App\Models\Roster\Referees\Referee::class)
                    <x-tables.empty-state
                        :title="__('referees.empty_title')"
                        :description="__('referees.empty_description')"
                        icon="heroicon-o-hand-raised"
                        data-test="referees-empty-state"
                    />
                @else
                    <x-tables.empty-state
                        :title="__('referees.empty_title')"
                        :description="__('referees.empty_read_only_description')"
                        icon="heroicon-o-hand-raised"
                        data-test="referees-empty-state"
                    />
                @endcan
            @endif
        @endif

        <x-tables.footer
            :paginator="$rows"
            :per-page-options="$perPageOptions"
            per-page-id="referees-per-page"
            per-page-model="perPage"
            :results-label="__('referees.results', ['first' => $rows->firstItem(), 'last' => $rows->lastItem(), 'total' => $rows->total()])"
            :per-page-label="__('referees.per_page')"
            :pagination-label="__('referees.pagination')"
            :previous-page-label="__('referees.previous_page')"
            :next-page-label="__('referees.next_page')"
            :page-label="__('referees.page', ['current' => $rows->currentPage(), 'last' => $rows->lastPage()])"
        />
    </section>
</div>
