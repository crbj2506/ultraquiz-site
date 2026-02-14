@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">{{ __('Lista de Questões') }}</div>
                <div class="card-header">
                    {{--
                        Formulário de filtros adicionados:
                        - envia via GET para permitir paginação preservando os parâmetros
                        - campos: f_pergunta (parte da pergunta), f_resposta (parte da resposta correta)
                    --}}
                    <form id="formFiltroQuestao" method="GET" action="{{ route('questao.index') }}" class="">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Filtros</span>
                            <input name="f_pergunta" type="text" class="form-control" placeholder="Parte da Pergunta" value="{{ request('f_pergunta') }}">
                            <input name="f_resposta" type="text" class="form-control" placeholder="Parte da Resposta Correta" value="{{ request('f_resposta') }}">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Filtrar</button>
                            <a href="{{ route('questao.index') }}" class="btn btn-sm btn-outline-success">Limpar</a>
                        </div>
                    </form>
                </div>
                <div class="card-body">

                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Pergunta</th>
                                <th scope="col">Resposta Correta</th>
                                <th scope="col">Respondida</th>
                                <th scope="col">Acertos</th>
                                <th scope="col">Erros</th>
                                <th scope="col">Fonte</th>
                                <th scope="col">Ver</th>
                                <th scope="col">Editar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($questoes as $key => $q)
                                <tr>
                                    <th scope="row">{{$q['id']}}</th>
                                    <td>{{$q['pergunta']}}</td>
                                    <td>{{$q['resposta']}}</td>
                                    <td>{{$q->estatisticas->count()}}</td>
                                    <td>{{$q->estatisticas->where('resposta_id', null)->count() }}</td>
                                    <td>{{$q->estatisticas->where('resposta_id', '!=' , null)->count() }}</td>
                                    <td><a href="{{$q['fonte']}}" target="_blank"> Fonte</a></td>
                                    <td><a href="{{ route('questao.show',['questao' => $q['id']])}}" class="btn btn-sm btn-outline-primary" class="btn btn-sm btn-outline-warning">Ver</a></td>
                                    <td><a href="{{ route('questao.edit',['questao' => $q['id']])}}" class="btn btn-sm btn-outline-warning">Editar</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{--
                    Componente de paginação reutilizável.
                    Observação: os parâmetros de filtro são preservados via ->appends($request->query()) no controller,
                    portanto os links de página manterão os filtros aplicados.
                --}}
                <x-paginacao :paginate="$questoes" />
            </div>
        </div>
    </div>
</div>
@endsection
