<div class="card-footer {{$p->lastPage() == 1 ? 'd-none' : ''}}">
    {{--$p->links() BUGADO!!!!!--}}
    <ul class="pagination pagination-sm justify-content-center">
        <li class="page-item {{ $p->currentPage() == 1 ? 'disabled' : ''}}">
            <a class="page-link" href="{{ $p->url(1) }}">Página 1</a>
        </li>
        <li class="page-item {{ $p->currentPage() == 1 ? 'disabled' : '' }}">
            <a class="page-link" href="{{ $p->previousPageUrl() }}" tabindex="-1" aria-disabled="true">Anterior</a>
        </li>

        @for ($i = 1;  $i <= $p->lastPage() ; $i++)
            <li class="page-item {{ $p->currentPage() == $i ? 'active' : '' }}
                                {{ ($i < $p->currentPage() - $p->d1) || ($i > $p->currentPage() + $p->d2) ? 'd-none' : '' }}">
                <a class="page-link" href="{{ $p->url($i) }}">{{ $i }}</a>
            </li>
        @endfor
        <li class="page-item">
            <a class="page-link {{ $p->currentPage() == $p->lastPage() ? 'disabled' : '' }}" href="{{ $p->nextPageUrl() }}">Próxima</a>
        </li>
        <li class="page-item">
            <a class="page-link {{ $p->currentPage() == $p->lastPage() ? 'disabled' : '' }}" href="{{ $p->url($p->lastPage()) }}">Página {{$p->lastPage()}}</a>
        </li>
    </ul>
</div>