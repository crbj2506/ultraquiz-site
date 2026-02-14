@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header">Lista de Sugestões de outros Jogadores para você avaliar</div>

                <table class="table">
                    <thead>
                        <tr>
                            <th class="text-center" scope="col">#</th>
                            <th class="text-center" scope="col">Pergunta</th>
                            <th class="text-center" scope="col">Resposta</th>
                            <th class="text-center" scope="col">Fonte</th>
                            <th class="text-center" scope="col">Criada por</th>
                            <th class="text-center" scope="col">Verificações</th>
                            <th class="text-center" scope="col">Aprovações</th>
                            <th class="text-center" scope="col">Reprovações</th>
                            <th class="text-center" scope="col">Ver</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sugestoes as $key => $s)
                            <tr>
                                <th class="text-center py-0" scope="row">{{$s->id}}</th>
                                <td class=" py-0" scope="row">{{$s->pergunta}}</td>
                                <td class=" py-0" scope="row">{{$s->resposta}}</td>
                                <td class=" py-0" scope="row">{{$s->fonte}}</td>
                                <td class=" py-0" scope="row">{{$s->user ? $s->user->name : ''}}</td>
                                <td class="text-center py-0" scope="row">{{$s->verificacoes->count()? $s->verificacoes->count(): 'Sem verificações'}}</td>
                                <td class="text-center py-0" scope="row">{{$s->verificacoes->count()? $s->verificacoes()->where('aprovada',1)->get()->count(): 'Sem Aprovações'}}</td>
                                <td class="text-center py-0" scope="row">{{$s->verificacoes->count()? $s->verificacoes()->where('aprovada',0)->get()->count(): 'Sem Reprovações'}}</td>
                                <td class="text-center py-0" scope="row"><a href="{{ route('sugestao.mostrar',['sugestao' => $s])}}" class="btn btn-sm btn-outline-primary py-0">Ver</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            <x-paginacao :paginate="$sugestoes" />
        </div>
    </div>
@endsection