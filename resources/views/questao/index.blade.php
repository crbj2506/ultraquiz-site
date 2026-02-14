@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">{{ __('Lista de Questões') }}</div>

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
                <x-paginacao :paginate="$questoes" />
            </div>
        </div>
    </div>
</div>
@endsection
