@if ($paginator->hasPages())
    <nav class="admin-pagination" aria-label="Pagination">
        <span>
            Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}
            · {{ $paginator->total() }} data
        </span>
        <div class="admin-pagination__links">
            @if ($paginator->onFirstPage())
                <span class="admin-pagination__link" aria-disabled="true">&lsaquo;</span>
            @else
                <a class="admin-pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">&lsaquo;</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="admin-pagination__link" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="admin-pagination__link is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="admin-pagination__link" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="admin-pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">&rsaquo;</a>
            @else
                <span class="admin-pagination__link" aria-disabled="true">&rsaquo;</span>
            @endif
        </div>
    </nav>
@endif
