@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">Partidas Ativas</h2>
            <p class="text-muted">Painel de supervisão das salas Multiplayer em tempo real.</p>
        </div>
        <div>
            <a href="{{ route('partida.index') }}" class="btn btn-outline-secondary rounded-pill fw-bold">Voltar para o Início</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">PIN</th>
                            <th>Status</th>
                            <th>Anfitrião</th>
                            <th>Criada Em</th>
                            <th>Jogadores Online</th>
                            <th class="text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($partidas as $partida)
                            <tr>
                                <td class="ps-4 fw-bold text-primary">{{ $partida->pin }}</td>
                                <td>
                                    @if($partida->status === 'waiting')
                                        <span class="badge bg-warning text-dark rounded-pill">No Lobby</span>
                                    @elseif($partida->status === 'playing')
                                        <span class="badge bg-success rounded-pill">Em Andamento</span>
                                    @endif
                                </td>
                                <td>{{ $partida->user->name ?? 'Desconhecido' }}</td>
                                <td>{{ $partida->created_at->format('d/m H:i') }}</td>
                                <td>
                                    @php
                                        $totalJogadores = $partida->equipes->sum(function($equipe) {
                                            return $equipe->jogadores->count();
                                        });
                                    @endphp
                                    <span class="badge bg-secondary rounded-pill">{{ $totalJogadores }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('lobby.sala', ['pin' => $partida->pin]) }}" class="btn btn-sm btn-outline-primary rounded-pill">Inspecionar Tela</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <span class="fs-1 d-block mb-3">👻</span>
                                    Nenhuma partida multiplayer ativa no momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
