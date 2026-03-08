@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white text-center fs-4 fw-bold py-3">
                    Multiplayer Cooperativo
                </div>
                <div class="card-body p-4 text-center">
                    
                    @if(session('error'))
                        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                            <div class="text-start">
                                <strong>Erro ao Entrar:</strong><br>
                                {{ session('error') }}
                            </div>
                        </div>
                    @endif

                    <div class="mb-5">
                        @auth
                            <h5 class="fw-bold mb-3 text-secondary">Entrar em uma Sala</h5>
                            <form action="{{ route('lobby.join') }}" method="POST">
                                @csrf
                                <div class="input-group input-group-lg w-75 mx-auto">
                                    <input type="text" name="pin" class="form-control text-center fw-bold text-uppercase fs-2" placeholder="PIN" maxlength="4" required autocomplete="off">
                                    <button class="btn btn-success fw-bold" type="submit">Entrar</button>
                                </div>
                            </form>
                        @else
                            <h5 class="fw-bold mb-3 text-secondary">Entrar como Convidado</h5>
                            <form action="{{ route('lobby.joinGuest') }}" method="POST">
                                @csrf
                                <div class="w-75 mx-auto">
                                    <input type="text" name="pin" class="form-control form-control-lg text-center fw-bold text-uppercase mb-2" placeholder="PIN DA SALA" maxlength="4" required>
                                    <input type="text" name="nickname" class="form-control form-control-lg text-center mb-3" placeholder="SEU NOME" maxlength="15" required>
                                    <button class="btn btn-success btn-lg fw-bold w-100" type="submit">ENTRAR NA SALA</button>
                                </div>
                            </form>
                            <div class="mt-4">
                                <p class="text-muted small">Dica: Convidados não acumulam XP, mas a diversão é a mesma!</p>
                            </div>
                        @endauth
                    </div>

                    <hr class="w-50 mx-auto text-muted mb-4">

                    <div>
                        <h5 class="fw-bold mb-3 text-secondary">Ser o Anfitrião</h5>
                        <form action="{{ route('lobby.create') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-lg fw-bold w-75">
                                Criar Nova Sala <x-icon-clipboard width="20" height="20" class="ms-1" />
                            </button>
                        </form>
                    </div>

                </div>
            </div>
            <div class="text-center mt-3">
                <a href="{{ route('partida.index') }}" class="text-decoration-none fw-bold text-muted">Voltar para Partida Solo</a>
            </div>
        </div>
    </div>
</div>
@endsection
