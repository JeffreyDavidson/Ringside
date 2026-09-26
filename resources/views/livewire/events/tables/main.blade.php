<div class="flex min-w-0 flex-col gap-6">
    <x-events.index.table-pre />

    <section
        class="border-ringside-line bg-ringside-surface-header min-w-0 border"
        aria-label="{{ __('events.index_title') }}"
    >
        <x-tables.status-filters
            :metadata="$this->metadata"
            :label="__('events.filter_status')"
            :all-label="__('events.all')"
            :selected="$filterValues['status'] ?? ''"
            status-id="events-status"
            test-id="events-status-filters"
        />

        <x-tables.toolbar
            id="events-search"
            model="search"
            :value="$search"
            :label="__('events.search')"
            :placeholder="__('events.search')"
            :clear-label="__('events.clear_search')"
        >
            <label for="events-venue" class="sr-only">{{ __('events.venue') }}</label>
            <div class="relative w-full sm:w-auto">
                <select
                    id="events-venue"
                    data-test="events-venue-filter"
                    wire:model.live="filterValues.venue"
                    class="border-ringside-line bg-ringside-surface text-ringside-ink focus-visible:outline-ringside-ink min-h-11 w-full appearance-none border py-2 ps-3 pe-10 text-sm focus-visible:outline-2 sm:w-auto"
                >
                    <option value="">{{ __('events.all_venues') }}</option>
                    @foreach ($this->getVenues as $venueId => $venueName)
                        <option value="{{ $venueId }}">{{ $venueName }}</option>
                    @endforeach
                </select>
                <x-heroicon-o-chevron-down
                    class="text-ringside-muted pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2"
                    aria-hidden="true"
                />
            </div>
            <details class="group relative w-full sm:w-auto">
                <summary class="border-ringside-line text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink focus-visible:outline-ringside-ink inline-flex min-h-11 cursor-pointer list-none items-center gap-2 border px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2">
                    {{ __('events.filters') }}
                    <x-heroicon-o-chevron-down
                        class="size-4 transition-transform group-open:rotate-180"
                        aria-hidden="true"
                    />
                </summary>
                <div class="border-ringside-line bg-ringside-surface-header mt-2 grid w-full gap-4 border p-4 shadow-xl sm:absolute sm:end-0 sm:top-full sm:z-20 sm:mt-2 sm:w-[22rem]">
                    <fieldset class="grid gap-2">
                        <legend class="text-ringside-muted text-xs font-medium">{{ __('events.date_range') }}</legend>
                        <div data-test="events-date-range-grid" class="grid grid-cols-1 gap-3 min-[380px]:grid-cols-2">
                            <div class="grid gap-1">
                                <label
                                    for="events-date-from"
                                    class="text-ringside-muted text-xs"
                                >{{ __('events.from') }}</label>
                                <input
                                    id="events-date-from"
                                    type="date"
                                    wire:model.live="filterValues.event_dates.minDate"
                                    class="border-ringside-line bg-ringside-surface text-ringside-ink focus-visible:outline-ringside-ink min-h-11 min-w-0 border px-2 text-sm focus-visible:outline-2"
                                />
                            </div>
                            <div class="grid gap-1">
                                <label
                                    for="events-date-to"
                                    class="text-ringside-muted text-xs"
                                >{{ __('events.to') }}</label>
                                <input
                                    id="events-date-to"
                                    type="date"
                                    wire:model.live="filterValues.event_dates.maxDate"
                                    class="border-ringside-line bg-ringside-surface text-ringside-ink focus-visible:outline-ringside-ink min-h-11 min-w-0 border px-2 text-sm focus-visible:outline-2"
                                />
                            </div>
                        </div>
                    </fieldset>
                    <button
                        type="button"
                        wire:click="clearFilters"
                        @disabled($search === '' && ($filterValues['status'] ?? '') === '' && ($filterValues['venue'] ?? '') === '' && empty($filterValues['event_dates']['minDate'] ?? null) && empty($filterValues['event_dates']['maxDate'] ?? null))
                        class="text-ringside-ink border-ringside-line hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink min-h-11 cursor-pointer justify-self-start border px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-default disabled:opacity-50"
                    >
                        {{ __('events.clear_filters') }}
                    </button>
                </div>
            </details>
            <span
                wire:loading.delay
                role="status"
                class="text-ringside-muted text-xs"
            >{{ __('events.updating') }}</span>
        </x-tables.toolbar>

        @if ($rows->isNotEmpty())
            <table class="w-full table-fixed border-collapse text-left text-sm" data-test="events-table">
                <caption class="sr-only">
                    {{ __('events.index_title') }}
                </caption>
                <thead class="border-ringside-line bg-ringside-surface text-ringside-muted border-y text-xs">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium sm:px-5">{{ __('events.name') }}</th>
                        <th scope="col" class="hidden w-44 px-4 py-3 font-medium sm:table-cell">
                            {{ __('events.date') }}
                        </th>
                        <th scope="col" class="hidden w-48 px-4 py-3 font-medium lg:table-cell">
                            {{ __('events.venue') }}
                        </th>
                        <th scope="col" class="hidden w-36 px-4 py-3 font-medium md:table-cell">
                            {{ __('core.status') }}
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
                                    href="{{ route('events.show', $row) }}"
                                    class="text-ringside-ink focus-visible:outline-ringside-ink font-semibold wrap-break-word underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
                                >{{ $row->name }}</a>
                                <p class="text-ringside-muted m-0 mt-1 text-xs leading-5 tabular-nums sm:hidden">
                                    {{ $row->date?->format('M j, Y') ?? __('events.no_date') }}
                                </p>
                                <p class="text-ringside-muted m-0 mt-1 text-xs leading-5 lg:hidden">
                                    @if ($row->venue)
                                        <a
                                            href="{{ route('venues.show', $row->venue) }}"
                                            class="text-ringside-ink underline-offset-4 hover:underline"
                                        >
                                            {{ $row->venue->name }}
                                        </a>
                                    @else
                                        {{ __('events.no_venue') }}
                                    @endif
                                </p>
                                <div class="mt-2 md:hidden"><x-tables.event-status :status="$row->status" /></div>
                            </td>
                            <td class="text-ringside-muted hidden px-4 py-4 align-top text-xs leading-5 tabular-nums sm:table-cell">
                                {{ $row->date?->format('M j, Y') ?? __('events.no_date') }}
                            </td>
                            <td class="hidden px-4 py-4 align-top text-xs leading-5 lg:table-cell">
                                @if ($row->venue)
                                    <a
                                        href="{{ route('venues.show', $row->venue) }}"
                                        class="text-ringside-ink underline-offset-4 hover:underline"
                                    >
                                        {{ $row->venue->name }}
                                    </a>
                                @else
                                    <span class="text-ringside-muted">{{ __('events.no_venue') }}</span>
                                @endif
                            </td>
                            <td class="hidden px-4 py-4 align-top md:table-cell">
                                <x-tables.event-status :status="$row->status" />
                            </td>
                            <td class="px-2 py-3 align-top"><x-tables.columns.event-actions :event="$row" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            @if ($search !== '' || ($filterValues['status'] ?? '') !== '' || ($filterValues['venue'] ?? '') !== '' || ! empty($filterValues['event_dates']['minDate'] ?? null) || ! empty($filterValues['event_dates']['maxDate'] ?? null))
                <x-tables.empty-state
                    :title="__('events.no_results_title')"
                    :description="__('events.no_results_description')"
                    icon="heroicon-o-calendar-days"
                    data-test="events-empty-state"
                >
                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="text-ringside-ink border-ringside-line hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink mt-2 min-h-11 cursor-pointer border px-4 text-sm focus-visible:outline-2 focus-visible:outline-offset-2"
                    >
                        {{ __('events.clear_filters') }}
                    </button>
                </x-tables.empty-state>
            @else
                @can('create', \App\Models\Events\Event::class)
                    <x-tables.empty-state
                        :title="__('events.empty_title')"
                        :description="__('events.empty_description')"
                        icon="heroicon-o-calendar-days"
                        data-test="events-empty-state"
                    />
                @else
                    <x-tables.empty-state
                        :title="__('events.empty_title')"
                        :description="__('events.empty_read_only_description')"
                        icon="heroicon-o-calendar-days"
                        data-test="events-empty-state"
                    />
                @endcan
            @endif
        @endif

        <x-tables.footer
            :paginator="$rows"
            :per-page-options="$perPageOptions"
            per-page-id="events-per-page"
            per-page-model="perPage"
            :results-label="__('events.results', ['first' => $rows->firstItem(), 'last' => $rows->lastItem(), 'total' => $rows->total()])"
            :per-page-label="__('events.per_page')"
            :pagination-label="__('events.pagination')"
            :previous-page-label="__('events.previous_page')"
            :next-page-label="__('events.next_page')"
            :page-label="__('events.page', ['current' => $rows->currentPage(), 'last' => $rows->lastPage()])"
        />
    </section>
</div>
