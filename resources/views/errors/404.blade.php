@extends('layouts.app')

@section('content')
<div class="container d-flex flex-column align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="card border-0 shadow-lg rounded-4 text-center p-5" style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); max-width: 500px; width: 100%;">
        <div class="display-1 fw-bold text-primary mb-3">404</div>
        <h2 class="mb-4 text-dark">Página ou Partida Não Encontrada</h2>
        <p class="text-muted mb-4 fs-5">
            A sala que você tentou acessar pode ter sido encerrada, ou o endereço digitado está incorreto.
        </p>
        <div class="d-grid gap-3">
            <a href="{{ route('lobby.index') }}" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm">
                Voltar para o Lobby
            </a>
            <a href="{{ route('partida.index') }}" class="btn btn-outline-secondary btn-lg rounded-pill fw-bold">
                Ir para o Início
            </a>
        </div>
    </div>
</div>
@endsection
