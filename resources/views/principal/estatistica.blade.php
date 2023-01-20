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
                                    <th class="py-0" scope="row">{{$e['id']}}</th>
                                    <td class="py-0">{{$e->resposta ? 'ERROU' : 'ACERTOU'}}</td>
                                    <td class="py-0">{{$e['questao_id']}}</td>
                                    <td class="py-0">{{$e->questao->pergunta}}</td>
                                    <td class="py-0">{{$e['resposta_id']}}</td>
                                    <td class="py-0">{{$e->resposta ? $e->resposta->alternativa : $e->questao->resposta}}</td>
                                    <td class="py-0">{{$e['created_at']}}</td>
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
