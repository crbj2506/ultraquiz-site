@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">Auditoria de Acoes Privilegiadas</div>
        <div class="card-body">
            <form method="GET" action="{{ route('log.index') }}" class="row g-2 mb-3">
                <div class="col-12 col-md-2">
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">Usuário (todos)</option>
                        @foreach($usuariosAuditaveis as $usuario)
                            <option value="{{ $usuario->id }}" {{ (string)($filtros['user_id'] ?? '') === (string)$usuario->id ? 'selected' : '' }}>
                                {{ $usuario->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="entidade" class="form-select form-select-sm">
                        <option value="">Entidade (todas)</option>
                        @foreach($entidades as $entidade)
                            <option value="{{ $entidade }}" {{ ($filtros['entidade'] ?? '') === $entidade ? 'selected' : '' }}>{{ ucfirst($entidade) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="metodo" class="form-select form-select-sm">
                        <option value="">Ação (todas)</option>
                        @foreach(['POST','PUT','PATCH','DELETE'] as $m)
                            <option value="{{ $m }}" {{ ($filtros['metodo'] ?? '') === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-1">
                    <input type="number" name="status_code" class="form-control form-control-sm" placeholder="Status HTTP" value="{{ $filtros['status_code'] ?? '' }}">
                </div>
                <div class="col-12 col-md-1">
                    <input type="text" name="rota" class="form-control form-control-sm" placeholder="Rota contém" value="{{ $filtros['rota'] ?? '' }}">
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" name="periodo_inicio" class="form-control form-control-sm" value="{{ $filtros['periodo_inicio'] ?? '' }}">
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" name="periodo_fim" class="form-control form-control-sm" value="{{ $filtros['periodo_fim'] ?? '' }}">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
                    <a href="{{ route('log.index') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
                </div>
            </form>

            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th class="text-center" scope="col">Data</th>
                        <th class="text-center" scope="col">Entidade</th>
                        <th class="text-center" scope="col">Ação</th>
                        <th class="text-center" scope="col">Status</th>
                        <th class="text-center" scope="col">Tipo</th>
                        <th class="text-center" scope="col">Origem</th>
                        <th class="text-center" scope="col">Rota</th>
                        <th class="text-center" scope="col">Usuário</th>
                        <th class="text-center" scope="col">Dados</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $dados = json_decode($log->dados, true) ?: [];
                            $entidade = $dados['entity'] ?? '-';
                            $metodo = $dados['method'] ?? '-';
                            $statusCode = $dados['status_code'] ?? '-';
                        @endphp
                        <tr>
                            <td class="text-center p-0">{{ $log->created_at }}</td>
                            <td class="text-center p-0">{{ strtoupper((string) $entidade) }}</td>
                            <td class="text-center p-0">{{ $metodo }}</td>
                            <td class="text-center p-0">{{ $statusCode }}</td>
                            <td class="text-center p-0">{{ $log->tipo }}</td>
                            <td class="text-center p-0">{{ $log->ip_origem }}</td>
                            <td class="p-0">{{ $log->rota }}</td>
                            <td class="text-center p-0">{{ $log->user ? $log->user->name : '' }}</td>
                            <td class="text-center p-0">
                                <button type="button" class="btn btn-sm btn-outline-secondary px-1 py-0 m-0" data-bs-toggle="modal" data-bs-target="#modal{{ $log->id }}">
                                    Dados
                                </button>
                            </td>
                        </tr>
                        <div class="modal fade" id="modal{{ $log->id }}" tabindex="-1" aria-labelledby="modal{{ $log->id }}Label" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5" id="modal{{ $log->id }}Label">Dados do LOG</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <pre>{{ $log->dados }}</pre>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3">Nenhum registro de auditoria encontrado para os filtros informados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-paginacao :paginate="$logs" />
    </div>
</div>
@endsection