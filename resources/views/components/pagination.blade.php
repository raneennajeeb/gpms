{{-- Used by $paginator->links() for every list (set as default in AppServiceProvider) --}}
@if ($paginator->hasPages())
    <nav class="pagination" aria-label="{{ __('pagination.label') }}">
        @if ($paginator->onFirstPage())
            <span class="pagination__link is-disabled">{{ __('pagination.previous') }}</span>
        @else
            <a class="pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('pagination.previous') }}</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination__dots">…</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination__link is-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pagination__link" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('pagination.next') }}</a>
        @else
            <span class="pagination__link is-disabled">{{ __('pagination.next') }}</span>
        @endif
    </nav>
@endif
