@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header fs-5 fw-bold">{{ __('Lista de Permissões') }}</div>

                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Permissão</th>
                                <th scope="col">Ver</th>
                                <th scope="col">Editar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($permissoes as $key => $p)
                                <tr>
                                    <th scope="row">{{$p['id']}}</th>
                                    <td>{{$p['permissao']}}</td>
                                    <td><a href="{{ route('permissao.show',['permissao' => $p['id']])}}" class="btn btn-sm btn-outline-primary" class="btn btn-sm btn-outline-warning">Ver</a></td>
                                    <td><a href="{{ route('permissao.edit',['permissao' => $p['id']])}}" class="btn btn-sm btn-outline-warning">Editar</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-paginacao :paginate="$permissoes" />
            </div>
        </div>
    </div>
</div>
@endsection
