<div>
    {{-- Before wrapper (configurable area for page header / add buttons) --}}
    @if($beforeWrapperView)
        @include($beforeWrapperView)
    @endif

    {{-- Card wrapper --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-light">
        {{-- Search and Filters --}}
        <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-gray-200">
            {{-- Search --}}
            <div class="flex items-center gap-2 bg-light-active rounded-md border border-gray-300 px-3 h-9 w-64">
                <x-heroicon-o-magnifying-glass class="size-4 text-gray-500 shrink-0" />
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ $searchPlaceholder }}"
                    class="grow bg-transparent border-none text-xs outline-none placeholder:text-gray-500 focus:ring-0 p-0 m-0"
                />
                @if($search)
                    <button wire:click="$set('search', '')" class="text-gray-400 hover:text-gray-600">
                        <x-heroicon-o-x-mark class="size-3.5" />
                    </button>
                @endif
            </div>

            {{-- Filters --}}
            @if(count($filters) > 0)
                <div class="flex items-center gap-3">
                    @foreach($filters as $filter)
                        @if($filter instanceof \App\Livewire\Table\Filters\SelectFilter)
                            <select
                                wire:model.live="filterValues.{{ $filter->getKey() }}"
                                class="appearance-none bg-light-active rounded-md border border-gray-300 font-medium text-xs h-9 px-2.5 text-gray-600 focus:border-primary focus:ring-0"
                            >
                                @foreach($filter->getOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="table-auto w-full text-left text-gray-700 font-medium text-sm border-collapse">
                <thead>
                    <tr>
                        @foreach($columns as $column)
                            <th class="bg-gray-100 text-gray-600 font-medium text-2sm align-middle py-2.5 px-4 border-b border-gray-200
                                {{ !$loop->last ? 'border-e border-e-gray-200' : '' }}
                                {{ $column->getTitle() === __('core.actions') ? 'w-[60px]' : '' }}"
                            >
                                @if($column->isSortable())
                                    <button wire:click="sort('{{ $column->getField() }}')" class="flex items-center gap-1 hover:text-gray-900">
                                        {{ $column->getTitle() }}
                                        @if($sortField === $column->getField())
                                            @if($sortDirection === 'asc')
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
                    @forelse($rows as $row)
                        <tr wire:key="row-{{ $row->{$this->primaryKey ?? 'id'} }}" class="border-b border-gray-200">
                            @foreach($columns as $column)
                                <td class="py-3 px-4
                                    {{ !$loop->last ? 'border-e border-e-gray-200' : '' }}"
                                >
                                    @if($column->isHtml())
                                        {!! $column->resolveValue($row) !!}
                                    @else
                                        {{ $column->resolveValue($row) }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) }}" class="py-8 px-4 text-center text-gray-500">
                                No records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($rows->hasPages())
            <div class="flex items-center justify-between px-5 py-4 border-t border-gray-200">
                <div class="flex items-center gap-2 text-xs text-gray-600">
                    <span>Per page:</span>
                    <select
                        wire:model.live="perPage"
                        class="appearance-none bg-light-active rounded-md border border-gray-300 font-medium text-xs h-8 px-2.5 w-16 focus:border-primary focus:ring-0"
                    >
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-1">
                    {{ $rows->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- Loading overlay --}}
    <div wire:loading.delay class="fixed inset-0 bg-white/50 z-50 flex items-center justify-center">
        <div class="text-gray-500 text-sm">Loading...</div>
    </div>
</div>
