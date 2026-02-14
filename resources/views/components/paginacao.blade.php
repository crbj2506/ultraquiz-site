@if($paginate && $paginate->lastPage() > 1)
    <div class="card-footer {{ $paginate->lastPage() == 1 ? 'd-none' : '' }}">
        {{--$paginate->links() BUGADO!!!!!--}}
        <ul class="pagination pagination-sm justify-content-center">
            <li class="page-item {{ $paginate->currentPage() == 1 ? 'disabled' : ''}}">
                <a class="page-link" href="{{ $paginate->url(1) }}">Página 1</a>
            </li>
            <li class="page-item {{ $paginate->currentPage() == 1 ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $paginate->previousPageUrl() }}" tabindex="-1" aria-disabled="true">Anterior</a>
            </li>

            @for ($i = 1;  $i <= $paginate->lastPage() ; $i++)
                <li class="page-item {{ $paginate->currentPage() == $i ? 'active' : '' }}
                                    {{ ($i < $paginate->currentPage() - $paginate->d1) || ($i > $paginate->currentPage() + $paginate->d2) ? 'd-none' : '' }}">
                    <a class="page-link" href="{{ $paginate->url($i) }}">{{ $i }}</a>
                </li>
            @endfor
            <li class="page-item">
                <a class="page-link {{ $paginate->currentPage() == $paginate->lastPage() ? 'disabled' : '' }}" href="{{ $paginate->nextPageUrl() }}">Próxima</a>
            </li>
            <li class="page-item">
                <a class="page-link {{ $paginate->currentPage() == $paginate->lastPage() ? 'disabled' : '' }}" href="{{ $paginate->url($paginate->lastPage()) }}">Página {{$paginate->lastPage()}}</a>
            </li>
        </ul>
    </div>
@endif