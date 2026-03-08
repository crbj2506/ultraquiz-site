@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-lg-10 col-xl-8 mx-auto mt-2">
            <div class="card shadow-lg border-0 overflow-hidden rounded-4">
                
                <!-- Header Integrado do Placar -->
                <div class="card-header bg-white border-bottom-0 p-4 pb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <?php 
                            $eqA = $partida->equipes[0] ?? null; 
                            $eqB = $partida->equipes[1] ?? null;
                        ?>
                        
                        <!-- Equipe A -->
                        @if($eqA)
                        <div class="text-center d-flex flex-column align-items-center" style="width: 35%">
                            <div class="rounded-circle bg-{{ $eqA->cor }} text-white d-flex align-items-center justify-content-center mb-2 shadow-sm" style="width: 50px; height: 50px; font-size: 1.25rem; font-weight: bold;">
                                {{ substr($eqA->nome, 0, 1) }}
                            </div>
                            <h6 class="fw-bold text-{{ $eqA->cor }} text-truncate w-100 mb-0" style="font-size: 0.9rem;">{{ $eqA->nome }}</h6>
                            <h2 class="fw-black mb-0 display-6" id="score-{{ $eqA->id }}">0</h2>
                            <div id="votos-{{ $eqA->id }}" class="mt-1 w-100 text-truncate" style="min-height: 20px;">
                                <small class="text-muted"><i class="bi bi-circle"></i></small>
                            </div>
                        </div>
                        @endif

                        <!-- VS e Fase -->
                        <div class="text-center d-flex flex-column justify-content-center align-items-center" style="width: 30%">
                            <div class="small fw-bold text-muted mb-2 bg-light px-2 py-1 rounded shadow-sm border" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                PIN: <span class="text-primary">{{ $partida->pin }}</span>
                            </div>
                            <span class="badge bg-dark rounded-pill px-3 py-2 mb-2 fs-6 shadow-sm" id="game-index" style="letter-spacing: 1px;">Q 0/0</span>
                            <h2 class="text-muted fw-black mb-0" style="opacity: 0.2; font-style: italic;">VS</h2>
                        </div>

                        <!-- Equipe B -->
                        @if($eqB)
                        <div class="text-center d-flex flex-column align-items-center" style="width: 35%">
                            <div class="rounded-circle bg-{{ $eqB->cor }} text-white d-flex align-items-center justify-content-center mb-2 shadow-sm" style="width: 50px; height: 50px; font-size: 1.25rem; font-weight: bold;">
                                {{ substr($eqB->nome, 0, 1) }}
                            </div>
                            <h6 class="fw-bold text-{{ $eqB->cor }} text-truncate w-100 mb-0" style="font-size: 0.9rem;">{{ $eqB->nome }}</h6>
                            <h2 class="fw-black mb-0 display-6" id="score-{{ $eqB->id }}">0</h2>
                            <div id="votos-{{ $eqB->id }}" class="mt-1 w-100 text-truncate" style="min-height: 20px;">
                                <small class="text-muted"><i class="bi bi-circle"></i></small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Barra de Progresso / Timer (Fina e Minimalista) -->
                <div class="progress rounded-0 bg-light" style="height: 6px;">
                    <div id="game-timer-bar" class="progress-bar bg-primary progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                </div>

                <!-- Área da Pergunta -->
                <div class="card-body p-4 p-md-5 text-center bg-light">
                    <div id="game-question-area">
                        <h2 id="game-question-text" class="fw-bold mb-4" style="line-height: 1.4;">Aguardando início...</h2>
                        <div id="game-alternatives" class="row g-3 mt-2 justify-content-center">
                            <!-- Alternativas inseridas via JS -->
                        </div>
                    </div>

                    <div id="game-loading" class="py-5 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-3 text-muted fw-bold" id="game-loading-msg">Preparando próxima questão...</p>
                    </div>
                </div>

                <!-- Footer Informativo -->
                @if(isset($minhaEquipe))
                <div class="card-footer bg-{{ $minhaEquipe->cor }} border-top-0 text-center py-2" style="--bs-bg-opacity: .15;">
                    <span class="text-{{ $minhaEquipe->cor }} fw-bold" style="font-size: 0.95rem;">
                        <i class="bi bi-flag-fill me-1"></i> Jogando pela <strong>{{ $minhaEquipe->nome }}</strong>
                    </span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const pin = "{{ $partida->pin }}";
        const userId = "{{ Auth::id() }}";
        const minhaEquipeId = "{{ $minhaEquipeId }}";
        let conn;
        let reconnectInterval = 1000;

        const questionText = document.getElementById('game-question-text');
        const alternativesDiv = document.getElementById('game-alternatives');
        const timerBar = document.getElementById('game-timer-bar');
        const indexBadge = document.getElementById('game-index');
        const loadingDiv = document.getElementById('game-loading');
        const questionArea = document.getElementById('game-question-area');

        function connect() {
            // Se estiver em HTTPS (PWA/Prod), usa a rota segura do proxy wss://.../app
            const protocol = window.location.protocol === 'https:' ? 'wss://' : 'ws://';
            const wsUrl = window.location.protocol === 'https:' 
                ? protocol + window.location.hostname + '/app'
                : protocol + window.location.hostname + ':8090';
                
            conn = new WebSocket(wsUrl);

            conn.onopen = function() {
                console.log("Connected to game server!");
                reconnectInterval = 1000; // Reset timer on success
                conn.send(JSON.stringify({ action: 'join', pin: pin, user_id: userId }));
            };

            conn.onmessage = function(e) {
                const data = JSON.parse(e.data);
                console.log("WebSocket message received:", data.type, data);
                
                if (data.type === 'pergunta') {
                    showQuestion(data);
                } else if (data.type === 'timer') {
                    updateTimer(data.tempo);
                } else if (data.type === 'fim_pergunta') {
                    highlightFimRodada(data);
                    // Espera 6 segundos mostrando o resultado antes do loading da próxima
                    setTimeout(() => showLoading(data.message), 6000); 
                } else if (data.type === 'voto') {
                    highlightVoto(data.user_id, data.equipe_id, data.alternativa_id);
                } else if (data.type === 'finalizada') {
                    showFinalResults(data);
                } else if (data.type === 'system') {
                    showSuddenDeath(data.message);
                } else if (data.type === 'rematch_status') {
                    updateRematchStatus(data);
                } else if (data.type === 'reset') {
                    // Se receber reset (Revanche), recarrega para resetar estado
                    window.location.reload();
                }
            };

            conn.onclose = function(e) {
                console.log("Socket is closed. Reconnect will be attempted in " + (reconnectInterval/1000) + " second(s).", e.reason);
                setTimeout(function() {
                    reconnectInterval = Math.min(reconnectInterval * 2, 30000); // Exponential backoff
                    connect();
                }, reconnectInterval);
            };

            conn.onerror = function(err) {
                console.error("Socket encountered error: ", err.message, "Closing socket");
                conn.close();
            };
        }

        connect(); // Initial connection

        function showQuestion(data) {
            loadingDiv.classList.add('d-none');
            questionArea.classList.remove('d-none');
            
            questionText.innerText = data.pergunta;
            indexBadge.innerText = `Questão ${data.index}/${data.total}`;
            
            // Limpa indicadores de votos da rodada anterior
            alternativesDiv.innerHTML = '';
            document.querySelectorAll('[id^="votos-"]').forEach(el => el.innerHTML = '<span class="text-muted">Aguardando...</span>');

            data.alternativas.forEach(alt => {
                const col = document.createElement('div');
                col.className = 'col-md-6';
                col.innerHTML = `
                    <button class="btn btn-outline-primary w-100 py-3 fw-bold alternative-btn shadow-sm" 
                            data-id="${alt.id}" onclick="votoLocal(${alt.id})">
                        ${alt.texto}
                        <div class="voters-area mt-2" id="voters-${alt.id}"></div>
                    </button>
                `;
                alternativesDiv.appendChild(col);
            });
            updateTimer(data.tempo || 60);
        }

        function updateTimer(tempo) {
            const percent = (tempo / 60) * 100;
            timerBar.style.width = percent + '%';
            
            let colorClass = 'bg-primary';
            if (tempo <= 10) colorClass = 'bg-danger';
            else if (tempo <= 30) colorClass = 'bg-warning';
            
            timerBar.className = `progress-bar ${colorClass} progress-bar-striped progress-bar-animated`;
        }

        function showLoading(msg) {
            questionArea.classList.add('d-none');
            loadingDiv.classList.remove('d-none');
            document.getElementById('game-loading-msg').innerText = msg;
        }

        function highlightFimRodada(data) {
            // Atualiza o placar lateral com os novos totais (se vierem no data)
            if (data.scores) {
                for (const [eid, score] of Object.entries(data.scores)) {
                    const scoreEl = document.getElementById('score-' + eid);
                    if (scoreEl) scoreEl.innerText = score;
                }
            }

            document.querySelectorAll('.alternative-btn').forEach(btn => {
                const altId = btn.dataset.id;
                btn.disabled = true;
                if (altId == data.id_correto) {
                    btn.classList.remove('btn-outline-primary', 'btn-primary');
                    btn.classList.add('btn-success', 'text-white', 'border-3');
                } else if (btn.classList.contains('btn-primary')) {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-danger', 'text-white');
                }
            });
        }

        function showFinalResults(data) {
            loadingDiv.classList.add('d-none');
            questionArea.classList.remove('d-none');
            // Remove a barra de progresso no fim
            timerBar.parentElement.remove();
            
            // Força o scroll para o topo para ver a mensagem
            window.scrollTo({ top: 0, behavior: 'smooth' });

            const resultados = data.resultados_finais;
            const winners = data.winners || [];
            const isWinner = winners.includes(parseInt(minhaEquipeId));
            const isTie = winners.length > 1;

            // Ordena os resultados pelas chaves (índices) para garantir ordem correta
            let resArray = [];
            const keys = Object.keys(resultados).sort((a, b) => parseInt(a) - parseInt(b));
            keys.forEach(k => resArray.push(resultados[k]));

            let messageHtml = '';
            if (isTie) {
                messageHtml = `
                    <div class="alert alert-warning border-0 shadow-sm py-4 mb-4">
                        <h1 class="display-4">⚖️ EMPATE! 🤝</h1>
                        <p class="lead mb-0">Foi um confronto épico e equilibrado! Nenhuma equipe conseguiu arrancar a vitória.</p>
                    </div>
                `;
            } else if (isWinner) {
                messageHtml = `
                    <div class="alert alert-success border-0 shadow-sm py-4 mb-4">
                        <h1 class="display-4">🎉 PARABÉNS! 🏆</h1>
                        <p class="lead mb-0">Sua equipe deu um show de conhecimento e venceu a partida!</p>
                    </div>
                `;
            } else {
                messageHtml = `
                    <div class="alert alert-info border-0 shadow-sm py-4 mb-4">
                        <h1 class="display-4">BOA TENTATIVA! 💪</h1>
                        <p class="lead mb-0">Não foi dessa vez, mas o importante é o aprendizado. Continue praticando para a próxima!</p>
                    </div>
                `;
            }

            questionArea.innerHTML = `
                ${messageHtml}
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-primary">Resultados Detalhados</h2>
                    <p class="text-muted">Veja como cada equipe se saiu em cada questão</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover shadow-sm bg-white">
                        <thead class="table-light text-center">
                            <tr>
                                <th style="width: 50px">#</th>
                                <th class="d-none d-md-table-cell">Pergunta</th>
                                <th class="d-none d-md-table-cell">Resposta Correta</th>
                                @foreach($partida->equipes as $eq)
                                    <th class="text-truncate" style="max-width: 100px;">{{ $eq->nome }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            ${resArray.map((res, i) => `
                                <tr>
                                    <td class="text-center fw-bold">${i + 1}</td>
                                    <td class="text-start small d-none d-md-table-cell">${res.pergunta}</td>
                                    <td class="text-center d-none d-md-table-cell"><span class="badge bg-success px-2 py-1">${res.correta}</span></td>
                                    ${Object.values(res.equipes || {}).map(eqRes => `
                                        <td class="text-center">
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="fs-4">${!eqRes.votou ? '➖' : (eqRes.acertou ? '✅' : '❌')}</span>
                                                <small class="text-muted d-none d-lg-block text-truncate" style="font-size: 0.65rem; max-width: 80px;">
                                                    ${!eqRes.votou ? 'Não respondeu' : eqRes.voto}
                                                </small>
                                            </div>
                                        </td>
                                    `).join('')}
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 pt-3 border-top text-center" id="rematch-area">
                    <p class="text-muted mb-3" id="rematch-msg">Deseja uma revanche? Ambas as equipes precisam aceitar!</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button id="btn-rematch" onclick="votarRematch()" class="btn btn-success btn-lg px-5 fw-bold shadow-sm">Aceitar Revanche 🔄</button>
                        <a href="{{ url('/') }}" class="btn btn-outline-primary btn-lg px-5 fw-bold shadow-sm">Sair</a>
                    </div>
                </div>
            `;
        }

        function updateRematchStatus(data) {
            const btn = document.getElementById('btn-rematch');
            const msg = document.getElementById('rematch-msg');
            if (!btn || !msg) return;

            const votaram = data.equipes_que_votaram || [];
            if (votaram.includes(parseInt(minhaEquipeId))) {
                btn.disabled = true;
                btn.innerHTML = 'Revanche Aceita! ✅';
                msg.innerHTML = '<span class="text-success fw-bold">Aguardando a outra equipe aceitar...</span>';
                msg.classList.add('animate__animated', 'animate__flash', 'animate__infinite');
            }
        }

        function showSuddenDeath(msg) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger fixed-top mt-5 mx-auto shadow-lg animate__animated animate__pulse animate__infinite';
            alert.style.width = 'fit-content';
            alert.style.zIndex = '9999';
            alert.innerHTML = `<h4 class="mb-0 fw-bold">🚀 ${msg}</h4>`;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 4000);
        }

        window.votarRematch = function() {
            if (conn && conn.readyState === WebSocket.OPEN) {
                conn.send(JSON.stringify({
                    action: 'rematch_vote',
                    pin: pin,
                    user_id: userId
                }));
            }
        }

        window.votoLocal = function(altId) {
            if (!conn || conn.readyState !== WebSocket.OPEN) {
                console.error("Não foi possível enviar o voto: Conexão offline.");
                return;
            }

            conn.send(JSON.stringify({
                action: 'votar',
                pin: pin,
                user_id: userId,
                alternativa_id: altId
            }));
            
            // Marca visualmente para mim na hora
            document.querySelectorAll('.alternative-btn').forEach(btn => {
                btn.classList.remove('btn-primary', 'text-white');
                btn.classList.add('btn-outline-primary');
            });
            const selected = document.querySelector(`[data-id="${altId}"]`);
            if (selected) {
                selected.classList.remove('btn-outline-primary');
                selected.classList.add('btn-primary', 'text-white');
            }
        };

        function highlightVoto(uid, eid, altId) {
            // 1. Atualiza o painel lateral para todos (Privacidade preservada: só diz que votou)
            const painelEquipe = document.getElementById('votos-' + eid);
                if (painelEquipe) { // Corrected variable name from 'painel' to 'painelEquipe'
                    painelEquipe.innerHTML = `<span class="badge bg-light text-dark shadow-sm border fw-bold"><i class="bi bi-check-circle-fill text-success"></i> Confirmado</span>`;
                }

            // 2. PRIVACIDADE: Só mostra o ponto na alternativa se for da MINHA EQUIPE
            if (eid == minhaEquipeId) {
                // Reseta visual de votos no placar centralizado
                document.querySelectorAll('[id^=votos-]').forEach(el => {
                    el.innerHTML = '<small class="text-muted"><i class="bi bi-circle"></i></small>';
                }); 
                // Remove o indicador desse usuário de outros botões
                document.querySelectorAll('.voter-dot').forEach(dot => {
                    if (dot.dataset.uid == uid) dot.remove();
                });

                // Adiciona o indicador no botão certo
                const area = document.getElementById('voters-' + altId);
                if (area) {
                    const dot = document.createElement('span');
                    dot.className = 'badge bg-info rounded-circle me-1 voter-dot';
                    dot.dataset.uid = uid;
                    dot.style.display = 'inline-block';
                    dot.style.width = '12px';
                    dot.style.height = '12px';
                    dot.title = "Companheiro de equipe";
                    area.appendChild(dot);
                }
            }
        }
    });
</script>
@endpush
@endsection
