@extends('layouts.app')

@section('content')
<div class="container d-flex flex-column align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="card border-0 shadow-lg rounded-4 text-center p-5" style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); max-width: 500px; width: 100%;">
        <div class="display-1 fw-bold text-danger mb-3">401</div>
        <h2 class="mb-4 text-dark">Acesso Não Autorizado</h2>
        <p class="text-muted mb-4 fs-5">
            Você precisa estar logado para acessar esta página ou sala. 
        </p>
        <div class="d-grid gap-3">
            <a href="{{ route('login') }}" class="btn btn-danger btn-lg rounded-pill fw-bold shadow-sm">
                Fazer Login
            </a>
            <a href="{{ route('lobby.index') }}" class="btn btn-outline-secondary btn-lg rounded-pill fw-bold">
                Voltar para o Lobby
            </a>
        </div>
    </div>
</div>
@endsection
