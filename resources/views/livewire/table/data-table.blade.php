<div class="flex flex-col gap-4">
    @if ($beforeWrapperView)
        <x-dynamic-component :component="$beforeWrapperView" />
    @endif

    <div class="border-ringside-line bg-ringside-surface-header border">
        <div class="border-ringside-line flex flex-wrap items-center justify-between gap-3 border-b p-4">
            <div class="border-ringside-line flex min-h-11 w-full items-center gap-2 border px-3 sm:w-72">
                <x-heroicon-o-magnifying-glass class="text-ringside-muted size-4 shrink-0" />
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ $searchPlaceholder }}"
                    class="text-ringside-ink placeholder:text-ringside-muted m-0 grow border-none bg-transparent p-0 text-sm outline-none focus:ring-0"
                />
                @if ($search)
                    <button
                        wire:click="$set('search', '')"
                        class="text-ringside-muted hover:text-ringside-ink"
                        aria-label="Clear search"
                    >
                        <x-heroicon-o-x-mark class="size-3.5" />
                    </button>
                @endif
            </div>

            @if (count($filters) > 0)
                <div class="flex flex-wrap items-center gap-3">
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
                </div>
            @endif
        </div>

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
                    @forelse ($rows as $row)
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
                                        {!! $column->resolveValue($row) !!}
                                    @else
                                        {{ $column->resolveValue($row) }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) }}" class="text-ringside-muted px-4 py-16 text-center">
                                No records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rows->hasPages())
            <div class="border-ringside-line flex flex-wrap items-center justify-between gap-4 border-t px-4 py-4">
                <div class="text-ringside-muted flex items-center gap-2 text-sm">
                    <span>Per page:</span>
                    <select
                        wire:model.live="perPage"
                        class="border-ringside-line bg-ringside-surface text-ringside-muted focus:border-ringside-ink min-h-10 w-20 appearance-none border px-3 text-sm focus:ring-0"
                    >
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-1">{{ $rows->links() }}</div>
            </div>
        @endif
    </div>

    <div
        wire:loading.delay
        class="border-ringside-line bg-ringside-surface-header text-ringside-muted fixed end-4 bottom-4 z-50 border px-4 py-3 text-sm shadow-xl"
    >
        Updating…
    </div>
</div>
