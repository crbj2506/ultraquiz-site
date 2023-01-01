@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">{{ __('Estatísticas') }}</div>

                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col"></th>
                                <th scope="col">Questão ID</th>
                                <th scope="col">Pergunta</th>
                                <th scope="col">Alternativa ID</th>
                                <th scope="col">Alternativa</th>
                                <th scope="col">Data da Tentativa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($estatisticas as $key => $e)
                                <tr>
                                    <th scope="row">{{$e['id']}}</th>
                                    <td>{{$e->resposta ? 'ERROU' : 'ACERTOU'}}</td>
                                    <td>{{$e['questao_id']}}</td>
                                    <td>{{$e->questao->pergunta}}</td>
                                    <td>{{$e['resposta_id']}}</td>
                                    <td>{{$e->resposta ? $e->resposta->alternativa : $e->questao->resposta}}</td>
                                    <td>{{$e['created_at']}}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{--$estatisticas->links() BUGADO!!!!!--}}
                    <ul class="pagination justify-content-center">
                        <li class="page-item">
                            <a class="page-link" href="{{ $estatisticas->url(1) }}"><<</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="{{ $estatisticas->previousPageUrl() }}" tabindex="-1" aria-disabled="true"><</a>
                        </li>@for ( $i= 1 ; $i <= $estatisticas->lastPage() ; $i++)
                            <li class="page-item {{ $estatisticas->currentPage() == $i ? 'active' : '' }}">
                                <a class="page-link" href="{{ $estatisticas->url($i) }}">{{ $i }}</a>
                            </li>
                        @endfor
                        <li class="page-item">
                            <a class="page-link" href="{{ $estatisticas->nextPageUrl() }}">></a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="{{ $estatisticas->url($estatisticas->lastPage()) }}">>></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
