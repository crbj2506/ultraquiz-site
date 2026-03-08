@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header text-center bg-dark text-white py-3">
                    <span class="fs-5 opacity-75">Sala:</span>
                    <span class="fs-2 fw-bolder ms-2 tracking-widest">{{ $partida->pin }}</span>
                </div>
                <div class="card-body bg-light">
                    
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="text-center mb-4">
                        <h4 class="fw-bold text-secondary">Aguardando jogadores...</h4>
                        <p class="text-muted">Escolha sua equipe para participar da partida cooperativa.</p>
                    </div>

                    <div class="row g-4">
                        @foreach($partida->equipes as $equipe)
                            <div class="col-md-6">
                                <div class="card h-100 border-{{ $equipe->cor }} border-2 shadow-sm">
                                    <div class="card-header bg-{{ $equipe->cor }} bg-opacity-10 text-{{ $equipe->cor }} fw-bold text-center fs-5 py-3">
                                        {{ $equipe->nome }}
                                    </div>
                                    <div class="card-body p-0">
                                        <ul class="list-group list-group-flush" id="lista-equipe-{{ $equipe->id }}">
                                            @forelse($equipe->jogadores as $jogador)
                                                <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                                    <span class="fw-bold text-dark">{{ $jogador->user->name }}</span>
                                                    @if(Auth::id() == $jogador->user_id)
                                                        <span class="badge bg-{{ $equipe->cor }} rounded-pill">Você</span>
                                                    @endif
                                                </li>
                                            @empty
                                                <li class="list-group-item text-center text-muted py-3 fst-italic">Aguardando...</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                    <div class="card-footer bg-white border-top-0 text-center pb-3" id="footer-equipe-{{ $equipe->id }}">
                                        <form class="escolher-equipe-form" action="{{ route('lobby.escolherEquipe', $partida->pin) }}" method="POST" data-equipe-id="{{ $equipe->id }}">
                                            @csrf
                                            <input type="hidden" name="equipe_id" value="{{ $equipe->id }}">
                                            @php
                                                $isMyTeam = ($jogando && $jogando->equipe_multiplayer_id == $equipe->id);
                                            @endphp
                                            <button type="submit" class="btn {{ $isMyTeam ? 'btn-'.$equipe->cor : 'btn-outline-'.$equipe->cor }} fw-bold w-100 choosing-btn" 
                                                    {{ $isMyTeam ? 'disabled' : '' }}>
                                                {{ $isMyTeam ? 'Sua Equipe' : 'Entrar na '.$equipe->nome }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($partida->user_id === Auth::id())
                        @php
                            $equipeA = $partida->equipes->where('nome', 'Equipe A')->first();
                            $equipeB = $partida->equipes->where('nome', 'Equipe B')->first();
                            $jogadoresEquipeA = $equipeA ? $equipeA->jogadores->count() : 0;
                            $jogadoresEquipeB = $equipeB ? $equipeB->jogadores->count() : 0;
                            $bloqueado = ($jogadoresEquipeA < 1 || $jogadoresEquipeB < 1);
                        @endphp
                        <div class="text-center mt-5">
                            <hr class="text-muted w-25 mx-auto mb-4">
                            <button type="button" id="iniciarPartidaBtn" class="btn btn-success btn-lg fw-bold px-5 shadow" {{ $bloqueado ? 'disabled' : '' }}>
                                INICIAR PARTIDA COOPERATIVA
                            </button>
                            @if($bloqueado)
                                <div id="msg-bloqueio-start" class="text-danger small mt-2">É necessário pelo menos 1 jogador em cada equipe para começar.</div>
                            @else
                                <div id="msg-bloqueio-start" class="text-danger small mt-2" style="display:none">É necessário pelo menos 1 jogador em cada equipe para começar.</div>
                            @endif
                            <div class="text-muted small mt-2">Apenas o anfitrião pode iniciar.</div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const pin = "{{ $partida->pin }}";
        const userId = "{{ Auth::id() }}";
        
        let conn;
        let reconnectInterval = 1000;

        function connect() {
            conn = new WebSocket('ws://' + window.location.hostname + ':8090');

            conn.onopen = function(e) {
                console.log("Connected to game server!");
                reconnectInterval = 1000; // Reset on success

                // Join the lobby room
                conn.send(JSON.stringify({
                    action: 'join',
                    pin: pin,
                    user_id: userId
                }));

                // Força uma atualização inicial ao conectar
                atualizarListaJogadores();
            };

            conn.onmessage = function(e) {
                const data = JSON.parse(e.data);
                console.log("WebSocket message received:", data.type, data);
                
                // Se alguém entrou, saiu ou mudou de equipe
                if (data.type === 'system' || data.type === 'join') {
                    atualizarListaJogadores();
                }

                // Se a partida foi iniciada, redireciona para a tela de jogo
                if (data.type === 'redirect' || data.type === 'pergunta') {
                    window.location.href = "{{ route('lobby.jogar', $partida->pin) }}";
                }
            };

            conn.onclose = function(e) {
                console.log(`Socket closed. Reconnect will be attempted in ${reconnectInterval/1000} second(s).`, e.reason);
                setTimeout(function() {
                    reconnectInterval = Math.min(reconnectInterval * 2, 30000); // Exponential backoff
                    connect();
                }, reconnectInterval);
            };

            conn.onerror = function(err) {
                console.error('Socket encountered error: ', err.message, 'Closing socket');
                conn.close();
            };
        }

        connect();

        function atualizarListaJogadores() {
            const cacheBust = new Date().getTime();
            fetch("/lobby/{{ $partida->pin }}/dados?t=" + cacheBust)
                .then(response => response.json())
                .then(data => {
                    console.log("JSON recebido do servidor:", data);
                    
                    data.equipes.forEach(equipe => {
                        const listaUl = document.getElementById('lista-equipe-' + equipe.id);
                        const footerDiv = document.getElementById('footer-equipe-' + equipe.id);
                        if (!listaUl || !footerDiv) return;

                        // 1. Atualiza Lista de Jogadores
                        let htmlLista = '';
                        let isMeInThisTeam = false;
                        
                        console.log(`Processando Equipe: ${equipe.nome}, Jogadores:`, equipe.jogadores);
                        
                        if (!equipe.jogadores || equipe.jogadores.length === 0) {
                            htmlLista = '<li class="list-group-item text-center text-muted py-3 fst-italic">Aguardando...</li>';
                        } else {
                            equipe.jogadores.forEach(jogador => {
                                const isMe = (jogador.user_id == userId);
                                if (isMe) isMeInThisTeam = true;
                                htmlLista += `
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <span class="fw-bold text-dark">${jogador.user.name}</span>
                                        ${isMe ? `<span class="badge bg-${equipe.cor} rounded-pill">Você</span>` : ''}
                                    </li>
                                `;
                            });
                        }
                        listaUl.innerHTML = htmlLista;

                        // 2. Atualiza Botões (Footer)
                        // IMPORTANTE: Reusamos o CSRF token do meta ou do Blade
                        const csrfToken = '{{ csrf_token() }}';
                        footerDiv.innerHTML = `
                            <form class="escolher-equipe-form" action="{{ route('lobby.escolherEquipe', $partida->pin) }}" method="POST" data-equipe-id="${equipe.id}">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <input type="hidden" name="equipe_id" value="${equipe.id}">
                                <button type="submit" class="btn ${isMeInThisTeam ? 'btn-'+equipe.cor : 'btn-outline-'+equipe.cor} fw-bold w-100 choosing-btn" 
                                        ${isMeInThisTeam ? 'disabled' : ''}>
                                    ${isMeInThisTeam ? 'Sua Equipe' : 'Entrar na ' + equipe.nome}
                                </button>
                            </form>
                        `;

                        atribuirListenerForm(footerDiv.querySelector('form'));
                    });

                    // Atualiza botão de Iniciar (se for o host)
                    const startBtn = document.getElementById('iniciarPartidaBtn');
                    if (startBtn) {
                        const equipeA = data.equipes.find(e => e.nome.includes('A'));
                        const equipeB = data.equipes.find(e => e.nome.includes('B'));
                        const countA = equipeA ? equipeA.jogadores.length : 0;
                        const countB = equipeB ? equipeB.jogadores.length : 0;
                        const bloqueado = (countA < 1 || countB < 1);
                        startBtn.disabled = bloqueado;
                        
                        const msgBloqueio = document.getElementById('msg-bloqueio-start');
                        if (msgBloqueio) msgBloqueio.style.display = bloqueado ? 'block' : 'none';
                    }
                    
                    // Se o status mudou para playing enquanto o player estava na sala
                    if (data.status === 'playing') {
                        window.location.href = "/lobby/{{ $partida->pin }}/jogar";
                    }
                });
        }

        const startBtn = document.getElementById('iniciarPartidaBtn');
        if (startBtn) {
            startBtn.addEventListener('click', function() {
                console.log("Start button clicked, sending fetch...");
                
                // 1. Avisa o Laravel para mudar o status para 'playing'
                fetch("/lobby/{{ $partida->pin }}/iniciar", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    console.log("Laravel response status:", response.status);
                    return response.json();
                })
                .then(data => {
                    console.log("Laravel response data:", data);
                    
                    // 2. Avisa o WebSocket para dar o "GO" para todos
                    if (conn.readyState === WebSocket.OPEN) {
                        console.log("Sending start message via WebSocket...");
                        conn.send(JSON.stringify({
                            action: 'start',
                            pin: pin,
                            user_id: userId
                        }));
                    } else {
                        console.error("Erro: Conexão WebSocket não está aberta.");
                        alert("Erro de conexão com o servidor de tempo real. Tente dar F5.");
                    }
                })
                .catch(err => {
                    console.error("Fetch error:", err);
                    alert("Ocorreu um erro ao tentar iniciar a partida. Verifique o console.");
                });
            });
        }

        function atribuirListenerForm(form) {
            if (!form) return; // Added check for form existence
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = this.querySelector('button');
                
                btn.disabled = true;
                btn.innerHTML = 'Entrando... <span class="spinner-border spinner-border-sm"></span>';

                fetch(this.action, {
                    method: 'POST',
                    body: new FormData(this),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => {
                    if (!res.ok) throw new Error("Erro na rede");
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        // Avisa os outros para atualizarem a lista
                        if (conn && conn.readyState === WebSocket.OPEN) { // Added conn check
                            conn.send(JSON.stringify({
                                action: 'join',
                                pin: pin,
                                user_id: userId
                            }));
                        }
                        atualizarListaJogadores();
                    }
                })
                .catch(err => {
                    console.error("Erro ao escolher equipe:", err);
                    btn.disabled = false;
                    btn.innerHTML = 'Tentar novamente';
                });
            });
        }

        // Inicializa listeners nos formulários iniciais
        document.querySelectorAll('.escolher-equipe-form').forEach(form => atribuirListenerForm(form));
    });
</script>
@endpush
@endsection
