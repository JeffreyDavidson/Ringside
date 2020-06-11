@if ($paginator->hasPages())
    <ul class="pagination">

        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <li class="paginate_button page-item first disabled" aria-disabled="true">
                <button type="button" class="page-link" aria-hidden="true">Previous</button>
            </li>
        @else
            <li class="paginate_button page-item previous">
                <button type="button" class="page-link" wire:click="previousPage" rel="prev">Previous</button>
            </li>
        @endif

        {{-- Pagination Elements --}}
        @foreach($elements as $element)
            @if (is_string($element))
                <li class="page-item disabled" aria-disabled="true">{{ $element }}</li>
            @endif

            @if (is_array($element))
                @foreach($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="paginate_button page-item active" aria-current="page">
                            <button type="button" class="page-link">{{ $page }}</button>
                        </li>
                    @else

                        @if (
                            $page > $paginator->lastPage() - 1 ||
                            $page == $paginator->onFirstPage() + 1 ||
                            $page == $paginator->currentPage() + 1 ||
                            $page == $paginator->currentPage() - 1 ||
                            $page <= $paginator->onFirstPage() + 5 ||
                            $paginator->currentPage() >= $paginator->lastPage() - 5 ||
                            ($page < $paginator->onFirstPage() + 5 && $paginator->currentPage() + 1 > $page)
                        )
                            <li class="paginate_button page-item" aria-current="page">
                                <button type="button" class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                            </li>
                        @else
                            {{-- <li class="paginate_button page-item disabled">
                                <button type="button" class="page-link" wire:click="gotoPage({{ $page }})">...</button>
                            </li> --}}
                        @endif
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <li class="paginate_button page-item last">
                <button type="button" class="page-link"
                        wire:click="nextPage" rel="next"
                        aria-label="Next"
                >Next</button>
            </li>
        @else
            <li class="paginate_button page-item next disabled" aria-disabled="true">
                <button type="button" class="page-link" aria-hidden="true">
                    Next
                </button>
            </li>
        @endif
    </ul>
@endif
