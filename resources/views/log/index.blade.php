@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">Log de Acesso</div>
        <div class="card-body">
            @foreach( $logs as $log)
                @if($loop->first)

                    <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="text-center" scope="col">Data</th>
                                    <th class="text-center" scope="col">Tipo</th>
                                    <th class="text-center" scope="col">Origem</th>
                                    <th class="text-center" scope="col">Rota</th>
                                    <th class="text-center" scope="col">Usuário</th>
                                    <th class="text-center" scope="col">Dados</th>
                                </tr>
                            </thead>
                            <tbody>
                @endif
                                <tr>
                                    <td class="text-center p-0">{{$log->created_at}}</td>
                                    <td class="text-center p-0">{{$log->tipo}}</td>
                                    <td class="text-center p-0">{{$log->ip_origem}}</td>
                                    <td class="p-0">{{$log->rota}}</td>
                                    <td class="text-center p-0">{{$log->user ? $log->user->name: ''}}</td>
                                    <td class="text-center p-0">
                                        <button type="button" class="btn btn-sm btn-outline-secondary px-1 py-0 m-0" data-bs-toggle="modal" data-bs-target="#modal{{$log->id}}">
                                            Dados
                                        </button>
                                    </td>
                                </tr>
                                <!-- Modal -->
                                <div class="modal fade" id="modal{{$log->id}}" tabindex="-1" aria-labelledby="modal{{$log->id}}Label" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5" id="modal{{$log->id}}Label">Dados do LOG</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <pre>{{$log->dados}}</pre>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                                    </div>
                                    </div>
                                </div>
                                </div>
                @if($loop->last)
                        </tbody>
                    </table>
                @endif
            @endforeach
        </div>
        <x-paginacao :paginate="$logs" />
    </div>
</div>
@endsection