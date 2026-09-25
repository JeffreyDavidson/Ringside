@props([
    'paginator',
    'perPageOptions',
    'perPageId',
    'perPageModel' => 'perPage',
    'resultsLabel',
    'perPageLabel',
    'paginationLabel',
    'previousPageLabel',
    'nextPageLabel',
    'pageLabel',
])

@if ($paginator->total() > 0)
    <footer class="border-ringside-line text-ringside-muted flex flex-wrap items-center justify-between gap-4 border-t px-4 py-3 text-xs">
        <p class="m-0 tabular-nums" aria-live="polite">{{ $resultsLabel }}</p>
        <div class="flex items-center gap-2">
            <label for="{{ $perPageId }}">{{ $perPageLabel }}</label>
            <div class="relative">
                <select
                    id="{{ $perPageId }}"
                    wire:model.live="{{ $perPageModel }}"
                    class="border-ringside-line bg-ringside-surface text-ringside-ink focus-visible:outline-ringside-ink min-h-11 appearance-none border py-2 ps-3 pe-8 text-sm focus-visible:outline-2"
                >
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
                <x-heroicon-o-chevron-down
                    class="pointer-events-none absolute end-2 top-1/2 size-3 -translate-y-1/2"
                    aria-hidden="true"
                />
            </div>
        </div>
        @if ($paginator->hasPages())
            <nav class="flex items-center gap-3" aria-label="{{ $paginationLabel }}">
                <button
                    type="button"
                    wire:click="previousPage"
                    wire:loading.attr="disabled"
                    @disabled($paginator->onFirstPage())
                    aria-label="{{ $previousPageLabel }}"
                    class="border-ringside-line hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink inline-flex size-11 cursor-pointer items-center justify-center border focus-visible:outline-2 disabled:cursor-default disabled:opacity-40"
                >
                    <x-heroicon-o-chevron-left class="size-4" aria-hidden="true" />
                </button>
                <span class="tabular-nums">{{ $pageLabel }}</span>
                <button
                    type="button"
                    wire:click="nextPage"
                    wire:loading.attr="disabled"
                    @disabled(! $paginator->hasMorePages())
                    aria-label="{{ $nextPageLabel }}"
                    class="border-ringside-line hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink inline-flex size-11 cursor-pointer items-center justify-center border focus-visible:outline-2 disabled:cursor-default disabled:opacity-40"
                >
                    <x-heroicon-o-chevron-right class="size-4" aria-hidden="true" />
                </button>
            </nav>
        @endif
    </footer>
@endif
