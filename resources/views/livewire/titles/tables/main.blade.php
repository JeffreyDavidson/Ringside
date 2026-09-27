<div class="flex min-w-0 flex-col gap-6">
    @if ($beforeWrapperView)
        <x-dynamic-component :component="$beforeWrapperView" />
    @endif

    <section
        class="border-ringside-line bg-ringside-surface-header min-w-0 border"
        aria-label="{{ __('titles.index_title') }}"
    >
        <x-tables.status-filters
            :metadata="$this->metadata"
            :label="__('titles.filter_status')"
            :all-label="__('titles.all')"
            :selected="$filterValues['status'] ?? ''"
            status-id="titles-status"
            test-id="titles-status-filters"
        />

        <div class="flex flex-wrap items-center justify-between gap-3 p-4">
            <x-tables.search-field
                id="titles-search"
                model="search"
                :value="$search"
                :label="__('titles.search')"
                :placeholder="__('titles.search')"
                :clear-label="__('titles.clear_search')"
            />
            <details class="group relative w-full sm:w-auto">
                <summary class="border-ringside-line text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink focus-visible:outline-ringside-ink inline-flex min-h-11 cursor-pointer list-none items-center gap-2 border px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2">
                    {{ __('titles.filters') }}
                    <x-heroicon-o-chevron-down
                        class="size-4 transition-transform group-open:rotate-180"
                        aria-hidden="true"
                    />
                </summary>
                <div class="border-ringside-line bg-ringside-surface-header mt-2 grid w-full gap-4 border p-4 shadow-xl sm:absolute sm:end-0 sm:top-full sm:z-20 sm:mt-2 sm:w-[22rem]">
                    <div class="grid gap-1">
                        <label for="titles-type" class="text-ringside-muted text-xs"> {{ __('titles.type') }} </label>
                        <div class="relative">
                            <select
                                id="titles-type"
                                wire:model.live="filterValues.type"
                                class="border-ringside-line bg-ringside-surface text-ringside-ink focus-visible:outline-ringside-ink min-h-11 w-full appearance-none border py-2 ps-3 pe-10 text-sm focus-visible:outline-2"
                            >
                                <option value="">{{ __('titles.all_types') }}</option>
                                @foreach (\App\Enums\Titles\TitleType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                            <x-heroicon-o-chevron-down
                                class="text-ringside-muted pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2"
                                aria-hidden="true"
                            />
                        </div>
                    </div>
                    <fieldset class="grid gap-2">
                        <legend class="text-ringside-muted text-xs">{{ __('titles.activation_date') }}</legend>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-1">
                                <label for="titles-date-from" class="text-ringside-muted text-xs">
                                    {{ __('titles.from') }}
                                </label>
                                <input
                                    id="titles-date-from"
                                    type="date"
                                    wire:model.live="filterValues.activation_date.minDate"
                                    class="border-ringside-line bg-ringside-surface text-ringside-ink focus-visible:outline-ringside-ink min-h-11 min-w-0 border px-2 text-sm focus-visible:outline-2"
                                />
                            </div>
                            <div class="grid gap-1">
                                <label for="titles-date-to" class="text-ringside-muted text-xs">
                                    {{ __('titles.to') }}
                                </label>
                                <input
                                    id="titles-date-to"
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
                        @disabled($search === '' && ($filterValues['status'] ?? '') === '' && ($filterValues['type'] ?? '') === '' && empty($filterValues['activation_date']['minDate'] ?? null) && empty($filterValues['activation_date']['maxDate'] ?? null))
                        class="text-ringside-ink border-ringside-line hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink min-h-11 cursor-pointer justify-self-start border px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-default disabled:opacity-50"
                    >
                        {{ __('titles.clear_filters') }}
                    </button>
                </div>
            </details>
            <span wire:loading.delay role="status" class="text-ringside-muted text-xs">
                {{ __('titles.updating') }}
            </span>
        </div>

        @if ($rows->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full table-fixed border-collapse text-left text-sm" data-test="titles-table">
                    <caption class="sr-only">
                        {{ __('titles.index_title') }}
                    </caption>
                    <thead class="border-ringside-line bg-ringside-surface text-ringside-muted border-y text-xs">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium sm:px-5">{{ __('titles.name') }}</th>
                            <th scope="col" class="hidden w-48 px-4 py-3 font-medium md:table-cell">
                                {{ __('titles.current_champion') }}
                            </th>
                            <th scope="col" class="hidden w-44 px-4 py-3 font-medium sm:table-cell">
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
                            @php
                                $champion = \App\Queries\Titles\TitleChampionshipQuery::currentChampion($row);
                            @endphp
                            <tr
                                wire:key="title-{{ $row->id }}"
                                class="hover:bg-ringside-surface-hover/40 transition-colors"
                            >
                                <td class="px-4 py-4 align-top sm:px-5">
                                    <a
                                        href="{{ route('titles.show', $row) }}"
                                        class="text-ringside-ink focus-visible:outline-ringside-ink font-semibold wrap-break-word underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
                                    >{{ $row->name }}</a>
                                    <p class="text-ringside-muted m-0 mt-1 text-xs leading-5">
                                        {{ $row->type->label() }}
                                    </p>
                                    <div class="mt-2 sm:hidden">
                                        <x-tables.title-status :status="$row->status" />
                                    </div>
                                    <p class="text-ringside-muted m-0 mt-2 text-xs leading-5 wrap-break-word md:hidden">
                                        @if ($champion instanceof \App\Models\Roster\Wrestlers\Wrestler)
                                            <a
                                                class="underline-offset-4 hover:underline"
                                                href="{{ route('wrestlers.show', $champion) }}"
                                            >{{ $champion->name }}</a>
                                        @elseif ($champion instanceof \App\Models\Roster\TagTeams\TagTeam)
                                            <a
                                                class="underline-offset-4 hover:underline"
                                                href="{{ route('tag-teams.show', $champion) }}"
                                            >{{ $champion->name }}</a>
                                        @else
                                            {{ __('titles.vacant') }}
                                        @endif
                                    </p>
                                    <p class="text-ringside-muted m-0 mt-2 text-xs leading-5 tabular-nums lg:hidden">
                                        {{ $row->firstActivityPeriod?->started_at?->format('M j, Y') ?? '—' }}
                                    </p>
                                </td>
                                <td class="hidden px-4 py-4 align-top md:table-cell">
                                    @if ($champion instanceof \App\Models\Roster\Wrestlers\Wrestler)
                                        <a
                                            class="text-ringside-ink focus-visible:outline-ringside-ink font-medium underline-offset-4 hover:underline focus-visible:outline-2"
                                            href="{{ route('wrestlers.show', $champion) }}"
                                        >{{ $champion->name }}</a>
                                    @elseif ($champion instanceof \App\Models\Roster\TagTeams\TagTeam)
                                        <a
                                            class="text-ringside-ink focus-visible:outline-ringside-ink font-medium underline-offset-4 hover:underline focus-visible:outline-2"
                                            href="{{ route('tag-teams.show', $champion) }}"
                                        >{{ $champion->name }}</a>
                                    @else
                                        <span class="text-ringside-muted">{{ __('titles.vacant') }}</span>
                                    @endif
                                </td>
                                <td class="hidden px-4 py-4 align-top sm:table-cell">
                                    <x-tables.title-status :status="$row->status" />
                                </td>
                                <td class="text-ringside-muted hidden px-4 py-4 align-top text-xs leading-5 tabular-nums lg:table-cell">
                                    {{ $row->firstActivityPeriod?->started_at?->format('M j, Y') ?? '—' }}
                                </td>
                                <td class="px-2 py-3 align-top">
                                    <x-tables.columns.title-actions :title="$row" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            @if ($search !== '' || ($filterValues['status'] ?? '') !== '' || ($filterValues['type'] ?? '') !== '' || ! empty($filterValues['activation_date']['minDate'] ?? null) || ! empty($filterValues['activation_date']['maxDate'] ?? null))
                <x-tables.empty-state
                    :title="__('titles.no_results_title')"
                    :description="__('titles.no_results_description')"
                    icon="heroicon-o-trophy"
                    data-test="titles-empty-state"
                >
                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="text-ringside-ink border-ringside-line hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink mt-2 min-h-11 cursor-pointer border px-4 text-sm focus-visible:outline-2 focus-visible:outline-offset-2"
                    >
                        {{ __('titles.clear_filters') }}
                    </button>
                </x-tables.empty-state>
            @else
                @can('create', \App\Models\Titles\Title::class)
                    <x-tables.empty-state
                        :title="__('titles.empty_title')"
                        :description="__('titles.empty_description')"
                        icon="heroicon-o-trophy"
                        data-test="titles-empty-state"
                    />
                @else
                    <x-tables.empty-state
                        :title="__('titles.empty_title')"
                        :description="__('titles.empty_read_only_description')"
                        icon="heroicon-o-trophy"
                        data-test="titles-empty-state"
                    />
                @endcan
            @endif
        @endif

        <x-tables.footer
            :paginator="$rows"
            :per-page-options="$perPageOptions"
            per-page-id="titles-per-page"
            per-page-model="perPage"
            :results-label="__('titles.results', ['first' => $rows->firstItem(), 'last' => $rows->lastItem(), 'total' => $rows->total()])"
            :per-page-label="__('titles.per_page')"
            :pagination-label="__('titles.pagination')"
            :previous-page-label="__('titles.previous_page')"
            :next-page-label="__('titles.next_page')"
            :page-label="__('titles.page', ['current' => $rows->currentPage(), 'last' => $rows->lastPage()])"
        />
    </section>
</div>
