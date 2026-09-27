<div class="flex min-w-0 flex-col gap-6">
    <x-tag-teams.index.table-pre />

    <section
        class="border-ringside-line bg-ringside-surface-header min-w-0 border"
        aria-label="{{ __('tag-teams.index_title') }}"
    >
        <x-tables.status-filters
            :metadata="$this->metadata"
            :label="__('tag-teams.filter_status')"
            :all-label="__('tag-teams.all')"
            :selected="$filterValues['status'] ?? ''"
        />

        <div class="flex flex-wrap items-center justify-between gap-3 p-4">
            <x-tables.search-field
                id="tag-teams-search"
                model="search"
                :value="$search"
                :label="__('tag-teams.search')"
                :placeholder="__('tag-teams.search')"
                :clear-label="__('tag-teams.clear_search')"
            />
            <span
                wire:loading.delay
                role="status"
                class="text-ringside-muted text-xs"
            >{{ __('tag-teams.updating') }}</span>
        </div>

        @if ($rows->isNotEmpty())
            <table class="w-full table-fixed border-collapse text-left text-sm" data-test="tag-team-table">
                <caption class="sr-only">
                    {{ __('tag-teams.index_title') }}
                </caption>
                <thead class="border-ringside-line bg-ringside-surface text-ringside-muted border-y text-xs">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium sm:px-5">{{ __('tag-teams.name') }}</th>
                        <th scope="col" class="hidden w-48 px-4 py-3 font-medium sm:table-cell">
                            {{ __('core.status') }}
                        </th>
                        <th scope="col" class="hidden w-36 px-4 py-3 font-medium xl:table-cell">
                            {{ __('employments.started_at') }}
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
                                    href="{{ route('tag-teams.show', $row) }}"
                                    class="text-ringside-ink focus-visible:outline-ringside-ink font-semibold wrap-break-word underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
                                >{{ $row->name }}</a>
                                <p class="text-ringside-muted m-0 mt-1 text-xs leading-5 wrap-break-word">
                                    {{ __('tag-teams.partners') }}: {{ $row->currentWrestlers->pluck('name')->join(' & ') ?: '—' }}
                                </p>
                                <div class="mt-2 sm:hidden"><x-tables.status :status="$row->status" /></div>
                            </td>
                            <td class="hidden px-4 py-4 align-top sm:table-cell">
                                <x-tables.status :status="$row->status" />
                            </td>
                            <td class="text-ringside-muted hidden px-4 py-4 align-top text-xs leading-5 tabular-nums xl:table-cell">
                                {{ $row->firstEmployment?->started_at?->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="px-2 py-3 align-top"><x-tables.columns.tag-team-actions :tag-team="$row" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            @if ($search !== '' || ($filterValues['status'] ?? '') !== '')
                <x-tables.empty-state
                    :title="__('tag-teams.no_results_title')"
                    :description="__('tag-teams.no_results_description')"
                    data-test="tag-team-empty-state"
                >
                    <button
                        type="button"
                        x-on:click="
                            $wire.set('search', '', false);
                            $wire.set('filterValues.status', '');
                        "
                        class="text-ringside-ink border-ringside-line hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink mt-2 min-h-11 cursor-pointer border px-4 text-sm focus-visible:outline-2 focus-visible:outline-offset-2"
                    >
                        {{ __('tag-teams.clear_filters') }}
                    </button>
                </x-tables.empty-state>
            @else
                @can('create', \App\Models\Roster\TagTeams\TagTeam::class)
                    <x-tables.empty-state
                        :title="__('tag-teams.empty_title')"
                        :description="__('tag-teams.empty_description')"
                        description-class="text-xs whitespace-nowrap sm:text-sm"
                        icon="heroicon-o-user-group"
                        data-test="tag-team-empty-state"
                    />
                @else
                    <x-tables.empty-state
                        :title="__('tag-teams.empty_title')"
                        :description="__('tag-teams.empty_read_only_description')"
                        icon="heroicon-o-user-group"
                        data-test="tag-team-empty-state"
                    />
                @endcan
            @endif
        @endif

        <x-tables.footer
            :paginator="$rows"
            :per-page-options="$perPageOptions"
            per-page-id="tag-teams-per-page"
            per-page-model="perPage"
            :results-label="__('tag-teams.results', ['first' => $rows->firstItem(), 'last' => $rows->lastItem(), 'total' => $rows->total()])"
            :per-page-label="__('tag-teams.per_page')"
            :pagination-label="__('tag-teams.pagination')"
            :previous-page-label="__('tag-teams.previous_page')"
            :next-page-label="__('tag-teams.next_page')"
            :page-label="__('tag-teams.page', ['current' => $rows->currentPage(), 'last' => $rows->lastPage()])"
        />
    </section>
</div>
