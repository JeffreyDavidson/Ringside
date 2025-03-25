<div class="inline-flex items-center gap-1">
    <div class="inline-flex items-center gap-1">
        @if ($paginator->hasPages())
            @php(isset($this->numberOfPaginatorsRendered[$paginator->getPageName()]) ? $this->numberOfPaginatorsRendered[$paginator->getPageName()]++ : $this->numberOfPaginatorsRendered[$paginator->getPageName()] = 1)

            <div class="flex justify-between flex-1 md:hidden">
                @if ($paginator->onFirstPage())
                    <button class="inline-flex items-center cursor-pointer rounded-md ps-px pe-px gap-1.5 border border-solid border-transparent font-medium outline-none shrink-0 justify-center size-[1.875rem] text-2sm p-0 leading-[0] text-gray-600">
                        {!! __('pagination.previous') !!}
                    </button>
                @else
                    <button class="inline-flex items-center cursor-pointer rounded-md ps-px pe-px gap-1.5 border border-solid border-transparent font-medium outline-none shrink-0 justify-center size-[1.875rem] text-2sm p-0 leading-[0] text-gray-600" type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before">
                        {!! __('pagination.previous') !!}
                    </button>
                @endif

                @if ($paginator->hasMorePages())
                    <button class="inline-flex items-center cursor-pointer rounded-md ps-px pe-px gap-1.5 border border-solid border-transparent font-medium outline-none shrink-0 justify-center size-[1.875rem] text-2sm p-0 leading-[0] text-gray-600 disabled" type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before">
                        {!! __('pagination.next') !!}
                    </button>
                @else
                    <button class="inline-flex items-center cursor-pointer rounded-md ps-px pe-px gap-1.5 border border-solid border-transparent font-medium outline-none shrink-0 justify-center size-[1.875rem] text-2sm p-0 leading-[0] text-gray-600">
                        {!! __('pagination.next') !!}
                    </button>
                @endif
            </div>

            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <button class="inline-flex items-center cursor-pointer rounded-md ps-px pe-px gap-1.5 border border-solid border-transparent font-medium outline-none shrink-0 justify-center size-[1.875rem] text-2sm p-0 leading-[0] text-gray-600 disabled" aria-hidden="true">
                    <i class="ki-outline ki-black-left text-gray-700 text-base"></i>
                </button>
            @else
                <button class="inline-flex items-center cursor-pointer rounded-md ps-px pe-px gap-1.5 border border-solid border-transparent font-medium outline-none shrink-0 justify-center size-[1.875rem] text-2sm p-0 leading-[0] text-gray-600" type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" rel="prev" aria-label="{{ __('pagination.previous') }}">
                    <i class="ki-outline ki-black-left text-gray-700 text-base"></i>
                </button>
            @endif

            {{-- Pagination Elements --}}
            @if ($elements ?? null)
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <button class="inline-flex items-center cursor-pointer rounded-md ps-px pe-px gap-1.5 border border-solid border-transparent font-medium outline-none shrink-0 justify-center size-[1.875rem] text-2sm p-0 leading-[0] text-gray-600">
                            {{ $element }}
                        </button>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <button class="inline-flex items-center cursor-pointer rounded-md ps-px pe-px gap-1.5 border border-solid border-transparent font-medium outline-none shrink-0 justify-center size-[1.875rem] text-2sm p-0 leading-[0] bg-gray-200 opacity-100 text-gray-500 pointer-events-none" wire:key="paginator-{{ $paginator->getPageName() }}-{{ $this->numberOfPaginatorsRendered[$paginator->getPageName()] }}-page{{ $page }}">{{ $page }}</button>
                            @else
                                <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" class="nline-flex items-center cursor-pointer rounded-md ps-px pe-px gap-1.5 border border-solid border-transparent font-medium outline-none shrink-0 justify-center size-[1.875rem] text-2sm p-0 leading-[0] text-gray-600 hover:bg-gray-200" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <button class="inline-flex items-center cursor-pointer rounded-md ps-px pe-px gap-1.5 border border-solid border-transparent font-medium outline-none shrink-0 justify-center size-[1.875rem] text-2sm p-0 leading-[0] text-gray-600" type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" rel="next" aria-label="{{ __('pagination.next') }}">
                    <i class="ki-outline ki-black-right text-gray-700 text-base"></i>
                </button>
            @else
                <button class="inline-flex items-center cursor-pointer rounded-md ps-px pe-px gap-1.5 border border-solid border-transparent font-medium outline-none shrink-0 justify-center size-[1.875rem] text-2sm p-0 leading-[0] text-gray-600 disabled" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                    <i class="ki-outline ki-black-right text-gray-700 text-base"></i>
                </button>
            @endif
        @endif
    </div>
</div>
