@if ($paginator->hasPages())
    <nav class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between" aria-label="Pagination">
        <p class="text-sm text-ink/55">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} sur {{ $paginator->total() }}
        </p>
        <div class="flex gap-2">
            @if ($paginator->onFirstPage())
                <span class="rounded-lg border border-line px-3 py-2 text-sm text-ink/30">Précédent</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="rounded-lg border border-line px-3 py-2 text-sm hover:bg-canvas">Précédent</a>
            @endif
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="rounded-lg border border-line px-3 py-2 text-sm hover:bg-canvas">Suivant</a>
            @else
                <span class="rounded-lg border border-line px-3 py-2 text-sm text-ink/30">Suivant</span>
            @endif
        </div>
    </nav>
@endif
