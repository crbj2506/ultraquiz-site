<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocketServer extends Command implements MessageComponentInterface
{
    protected $signature = 'websocket:serve {--port=8090}';
    protected $description = 'Start the WebSocket server for UltraQuiz Multiplayer';

    protected $clients;
    protected $salas; // [ pin => [ 'conns' => SplObjectStorage, 'timer' => ..., 'votos' => [team_id => alt_id], 'player_equipe' => [uid => eid] ] ]
    protected $loop;

    public function __construct()
    {
        parent::__construct();
        $this->clients = new \SplObjectStorage;
        $this->salas = [];
    }

    public function handle()
    {
        $port = $this->option('port');
        
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    $this
                )
            ),
            $port,
            '0.0.0.0'
        );

        $this->info("WebSocket Server started on port {$port} explicitly bound to all interfaces (0.0.0.0)");
        $this->loop = $server->loop;
        $server->run();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        try {
            $data = json_decode($msg, true);
            if (!$data || !isset($data['action'])) return;

            $action = $data['action'];
            $pin = isset($data['pin']) ? strtoupper(trim($data['pin'])) : null;
            $user_id = $data['user_id'] ?? null;

            echo "Mensagem recebida: Action={$action}, PIN={$pin}, User={$user_id}\n";

            if ($action === 'join' && $pin) {
                if (!isset($this->salas[$pin])) {
                    $this->salas[$pin] = [
                        'conns' => new \SplObjectStorage,
                        'votos' => [],
                        'votos_detalhados' => [], // [equipe_id => [user_id => ['alt' => alt_id, 'time' => time]]]
                        'votos_jogadores' => [], // Tracks individual votes: [user_id => bool]
                        'player_equipe' => [],
                        'resultados' => [], // [index => ['pergunta' => '...', 'correta' => '...', 'equipes' => [eid => ['voto' => '...', 'acertou' => bool]]]]
                        'rematch_votos' => [], // [team_id => bool]
                        'status' => 'idle' // idle, playing, waiting_next
                    ];
                }
                // Sempre anexa a conexão à sala
                $this->salas[$pin]['conns']->attach($from, $user_id);
                echo "User $user_id joined/refreshed Sala $pin (Conn {$from->resourceId})\n";

                // Busca a equipe do jogador no DB e armazena em memória para rapidez
                $jogador = \App\Models\JogadorPartida::where('user_id', $user_id)
                    ->whereHas('partida', function($q) use ($pin) {
                        $q->where('pin', $pin);
                    })->first();
                
                if ($jogador) {
                    $this->salas[$pin]['player_equipe'][$user_id] = $jogador->equipe_multiplayer_id;
                }

                // Avisa os OUTROS que alguém entrou (Throttled or Simple)
                $this->broadcastToSala($pin, [
                    'type' => 'system',
                    'user_id' => $user_id,
                    'message' => "Um jogador entrou."
                ], $from);
                
                // SINCRONIZAÇÃO / GATILHO
                $pm = \App\Models\PartidaMultiplayer::where('pin', $pin)->first();
                if ($pm && $pm->status === 'playing') {
                    echo "Sala $pin está em 'playing'. Verificando necessidade de rodada...\n";
                    $statusSala = $this->salas[$pin]['status'] ?? 'idle';
                    
                    if ($statusSala === 'idle' || $statusSala === 'playing') {
                        if (!isset($this->salas[$pin]['timer'])) {
                            echo "Iniciando proximaPergunta na Sala $pin para User $user_id\n";
                            $this->proximaPergunta($pin);
                        } else {
                            echo "Sincronizando pergunta atual da Sala $pin para User $user_id\n";
                            $this->enviarPerguntaAtualParaCliente($pm, $from);
                        }
                    } else if ($statusSala === 'waiting_next') {
                        echo "Aguardando próxima questão (em intervalo). Sincronizando info...\n";
                        // Se estiver no intervalo de 4s, não faz nada, o timer original vai disparar
                    }
                } else {
                    $statusDb = $pm ? $pm->status : 'N/A';
                    echo "Sala $pin não está pronta para sync (Status DB: $statusDb)\n";
                }
            }

            if ($action === 'votar' && $pin && isset($data['alternativa_id'])) {
                $equipe_id = $this->salas[$pin]['player_equipe'][$user_id] ?? null;
                if (!$equipe_id) return;

                // Registra o voto detalhado (Maioria com desempate de tempo)
                if (!isset($this->salas[$pin]['votos_detalhados'][$equipe_id])) {
                    $this->salas[$pin]['votos_detalhados'][$equipe_id] = [];
                }
                $this->salas[$pin]['votos_detalhados'][$equipe_id][$user_id] = [
                    'alt' => $data['alternativa_id'],
                    'time' => microtime(true)
                ];

                // Registra que o jogador votou na rodada
                $this->salas[$pin]['votos_jogadores'][$user_id] = true;

                echo "[VOTAR] Sala: $pin | User: $user_id | Equipe: $equipe_id | Alt: {$data['alternativa_id']}\n";

                // Avisa a todos sobre o voto (para brilhar a alternativa no front)
                $this->broadcastToSala($pin, [
                    'type' => 'voto',
                    'user_id' => $user_id,
                    'equipe_id' => $equipe_id,
                    'alternativa_id' => $data['alternativa_id']
                ]);

                // Verifica se TODOS os jogadores conectados já votaram
                $connectedUserIds = [];
                $conns = $this->salas[$pin]['conns'];
                foreach ($conns as $conn) {
                    $uid = $conns->getInfo();
                    // Verifica se o usuário tem afiliação a uma equipe (é um jogador válido)
                    if ($uid && isset($this->salas[$pin]['player_equipe'][$uid])) {
                        $connectedUserIds[] = $uid;
                    }
                }
                $connectedUserIds = array_unique($connectedUserIds);

                $todosVotaram = true;
                foreach ($connectedUserIds as $uid) {
                    if (!isset($this->salas[$pin]['votos_jogadores'][$uid])) {
                        $todosVotaram = false;
                        break;
                    }
                }

                if ($todosVotaram && count($connectedUserIds) > 0) {
                    echo "Todos os " . count($connectedUserIds) . " jogadores conectados votaram na Sala $pin. Pulando timer!\n";
                    $this->processarFimDaPergunta($pin, "Todos votaram!");
                }
            }

            if ($action === 'start' && $pin) {
                echo "Comando START recebido para Sala $pin. Redirecionando todos para a tela de jogo...\n";
                if (isset($this->salas[$pin])) {
                    $this->salas[$pin]['status'] = 'playing';
                }
                $this->broadcastToSala($pin, [
                    'type' => 'redirect'
                ]);
            }

            if ($action === 'rematch_vote' && $pin) {
                $equipe_id = $this->salas[$pin]['player_equipe'][$user_id] ?? null;
                if (!$equipe_id) return;

                echo "Voto de revanche recebido: Sala $pin, Equipe $equipe_id\n";
                $this->salas[$pin]['rematch_votos'][$equipe_id] = true;

                $equipesAtivas = array_unique(array_values($this->salas[$pin]['player_equipe']));
                $votosAtuais = array_keys(array_filter($this->salas[$pin]['rematch_votos']));
                
                // Verifica se pelo menos um de cada equipe ativa votou
                $todosAceitaram = true;
                foreach ($equipesAtivas as $eid) {
                    if (!isset($this->salas[$pin]['rematch_votos'][$eid])) {
                        $todosAceitaram = false;
                        break;
                    }
                }

                if ($todosAceitaram && count($equipesAtivas) > 1) {
                    echo "Revanche aceita por ambas as equipes na Sala $pin! Reiniciando...\n";
                    $pm = \App\Models\PartidaMultiplayer::with('equipes')->where('pin', $pin)->first();
                    if ($pm) {
                        $p = new \App\Models\Partida();
                        $p->criar();
                        $qs = array_slice($p->getState()['questoes_data'], 0, 3);
                        $pm->update(['status' => 'playing', 'questoes_json' => json_encode($qs), 'pergunta_atual_index' => 0]);
                        foreach ($pm->equipes as $eq) { $eq->update(['pontuacao' => 0]); }
                        $this->salas[$pin]['votos'] = [];
                        $this->salas[$pin]['votos_detalhados'] = [];
                        $this->salas[$pin]['votos_jogadores'] = [];
                        $this->salas[$pin]['resultados'] = [];
                        $this->salas[$pin]['rematch_votos'] = [];
                        $this->broadcastToSala($pin, ['type' => 'reset']);
                        $this->proximaPergunta($pin);
                    }
                } else {
                    // Avisa o progresso da votação
                    $this->broadcastToSala($pin, [
                        'type' => 'rematch_status',
                        'equipes_que_votaram' => $votosAtuais,
                        'aguardando' => array_values(array_diff($equipesAtivas, $votosAtuais))
                    ]);
                }
            }
        } catch (\Exception $e) {
            echo "Erro em onMessage: " . $e->getMessage() . "\n";
        }
    }

    private function proximaPergunta($pin)
    {
        $pm = \App\Models\PartidaMultiplayer::where('pin', $pin)->first();
        if (!$pm || !$pm->questoes_json) {
            echo "Erro: Partida ou questões não encontradas para Sala $pin\n";
            return;
        }

        $questoesArray = json_decode($pm->questoes_json, true);
        $index = $pm->pergunta_atual_index;

        if (!$questoesArray || $index >= count($questoesArray)) {
            echo "Partida finalizada ou sem questões para Sala $pin\n";
            $this->finalizarPartida($pin);
            return;
        }

        $this->salas[$pin]['votos'] = []; // Limpa os votos para a nova rodada
        $this->salas[$pin]['votos_detalhados'] = [];
        $this->salas[$pin]['votos_jogadores'] = [];
        $this->salas[$pin]['status'] = 'playing';
        $broadcastData = $this->prepararDadosPergunta($pm, $questoesArray, $index);
        echo "Enviando Pergunta #".($index+1)." para Sala $pin\n";
        $this->broadcastToSala($pin, $broadcastData);

        $this->iniciarTimer($pin, 60);
    }

    private function enviarPerguntaAtualParaCliente($pm, $conn)
    {
        $questoesArray = json_decode($pm->questoes_json, true);
        $index = $pm->pergunta_atual_index;
        if ($index >= count($questoesArray)) return;

        $data = $this->prepararDadosPergunta($pm, $questoesArray, $index);
        
        // Se houver um timer rodando, manda o tempo REAL restante
        if (isset($this->salas[$pm->pin]['tempo_restante'])) {
            $data['tempo'] = $this->salas[$pm->pin]['tempo_restante'];
        }

        $conn->send(json_encode($data));
        echo "Sincronizando Pergunta #".($index+1)." com novo cliente na Sala {$pm->pin}\n";
    }

    private function prepararDadosPergunta($pm, $questoesArray, $index)
    {
        if (!$questoesArray || !isset($questoesArray[$index])) {
            throw new \Exception("Dados da questão no índice $index não encontrados.");
        }

        $qData = $questoesArray[$index];
        
        // DEBUG
        $debugKeys = is_array($qData) ? implode(',', array_keys($qData)) : 'NOT ARRAY';
        $debugId = is_array($qData) ? ($qData['id'] ?? 'NO ID') : 'N/A';
        echo "Preparando Pergunta $index (Sala {$pm->pin}). IDs em qData: [$debugKeys], ID: $debugId\n";

        $questaoReal = \App\Models\Questao::with('respostas')->find($qData['id']);
        
        if (!$questaoReal) {
            throw new \Exception("Questão ID {$qData['id']} não encontrada no banco de dados.");
        }

        $alternativas = [];
        foreach ($qData['opcoes'] as $respId) {
            if ($respId === 0) {
                $alternativas[] = ['id' => 0, 'texto' => $questaoReal->resposta];
            } else {
                $resp = $questaoReal->respostas->find($respId);
                if ($resp) $alternativas[] = ['id' => $resp->id, 'texto' => $resp->alternativa];
            }
        }

        return [
            'type' => 'pergunta',
            'pergunta' => $questaoReal->pergunta,
            'alternativas' => $alternativas,
            'tempo' => 60,
            'index' => $index + 1,
            'total' => count($questoesArray)
        ];
    }

    private function iniciarTimer($pin, $segundos)
    {
        if (isset($this->salas[$pin]['timer'])) {
            $this->loop->cancelTimer($this->salas[$pin]['timer']);
        }

        $this->salas[$pin]['tempo_restante'] = $segundos;
        $this->salas[$pin]['timer'] = $this->loop->addPeriodicTimer(1, function() use ($pin) {
            $this->salas[$pin]['tempo_restante']--;
            
            $this->broadcastToSala($pin, [
                'type' => 'timer',
                'tempo' => $this->salas[$pin]['tempo_restante']
            ]);

            if ($this->salas[$pin]['tempo_restante'] <= 0) {
                $this->loop->cancelTimer($this->salas[$pin]['timer']);
                $this->processarFimDaPergunta($pin);
            }
        });
    }

    private function processarFimDaPergunta($pin, $motivo = 'Tempo esgotado!')
    {
        if (isset($this->salas[$pin]['timer'])) {
            $this->loop->cancelTimer($this->salas[$pin]['timer']);
            unset($this->salas[$pin]['timer']);
        }

        $pm = \App\Models\PartidaMultiplayer::where('pin', $pin)->first();
        $questoesArray = json_decode($pm->questoes_json, true);
        $index = $pm->pergunta_atual_index;
        
        $qData = $questoesArray[$index];
        $questaoReal = \App\Models\Questao::with('respostas')->find($qData['id']);
        $respostaCorretaTexto = $questaoReal->resposta;
        $idCorreto = 0; // No nosso sistema 0 é a correta original

        // Apuração
        $resEquipes = [];
        $equipesNaSala = array_unique(array_values($this->salas[$pin]['player_equipe']));
        
        echo "[APURAÇÃO] Sala: $pin | Correta: $idCorreto\n";

        foreach ($equipesNaSala as $eid) {
            $votosEquipe = $this->salas[$pin]['votos_detalhados'][$eid] ?? [];
            $votoId = null;

            if (count($votosEquipe) > 0) {
                $contagem = []; // [alt => count]
                $primeirosTempos = []; // [alt => min_time]
                
                // Consolida os votos da equipe
                foreach ($votosEquipe as $uid => $vdata) {
                    $alt = $vdata['alt'];
                    $time = $vdata['time'];
                    
                    if (!isset($contagem[$alt])) {
                        $contagem[$alt] = 0;
                        $primeirosTempos[$alt] = $time;
                    }
                    $contagem[$alt]++;
                    
                    // Guarda o tempo do voto mais rápido para aquela alternativa
                    if ($time < $primeirosTempos[$alt]) {
                        $primeirosTempos[$alt] = $time;
                    }
                }
                
                // Encontra a maioria ou empates na maioria
                $maxVotos = max($contagem);
                $empatados = [];
                foreach ($contagem as $alt => $qtd) {
                    if ($qtd === $maxVotos) {
                        $empatados[] = $alt;
                    }
                }
                
                if (count($empatados) === 1) {
                    $votoId = $empatados[0]; // Maioria absoluta
                } else {
                    // Desempate pelo "Dedo Mais Rápido"
                    $vencedorTempo = null;
                    $melhorTempo = null;
                    foreach ($empatados as $alt) {
                        if ($melhorTempo === null || $primeirosTempos[$alt] < $melhorTempo) {
                            $melhorTempo = $primeirosTempos[$alt];
                            $vencedorTempo = $alt;
                        }
                    }
                    $votoId = $vencedorTempo;
                    echo "Sala $pin: Equipe $eid empatou internamente. Desempate por velocidade: Alt $votoId venceu.\n";
                }
            }

            $acertou = ($votoId !== null && $votoId == $idCorreto);
            
            $textoVoto = "Não votou";
            if ($votoId !== null) {
                if ($votoId == 0) {
                    $textoVoto = $questaoReal->resposta;
                } else {
                    $resp = $questaoReal->respostas->find($votoId);
                    $textoVoto = $resp ? $resp->alternativa : "Erro";
                }
            }

            $resEquipes[$eid] = [
                'voto' => $textoVoto,
                'acertou' => $acertou,
                'votou' => ($votoId !== null)
            ];

            $equipeObj = \App\Models\EquipeMultiplayer::with('jogadores.user')->find($eid);

            // Registra cada resposta individual no histórico global (/estatistica).
            if (!empty($votosEquipe)) {
                $agora = now();
                $estatisticas = [];

                foreach ($votosEquipe as $vdata) {
                    $alternativaId = (int)($vdata['alt'] ?? -1);
                    if ($alternativaId < 0) {
                        continue;
                    }

                    $estatisticas[] = [
                        'questao_id' => $questaoReal->id,
                        'resposta_id' => $alternativaId === 0 ? null : $alternativaId,
                        'created_at' => $agora,
                        'updated_at' => $agora,
                    ];
                }

                if (!empty($estatisticas)) {
                    \App\Models\Estatistica::insert($estatisticas);
                }
            }
            
            if ($votoId !== null) {
                // Item 6: Atualiza estatísticas globais da Questão
                if ($acertou) {
                    $questaoReal->increment('acertos');
                } else {
                    $questaoReal->increment('erros');
                }

                // Item 7: Elo Progression System
                if ($equipeObj) {
                    foreach ($equipeObj->jogadores as $jogador) {
                        // is_guest is tracked as a boolean or 1. If not guest, calculate XP.
                        if ($jogador->user && empty($jogador->user->is_guest)) {
                            $jogador->user->adicionarExperienciaBaseadoNaQuestao($questaoReal, $acertou);
                        }
                    }
                }
            }

            // Score clássico da Partida (10 pontos por acerto)
            if ($acertou && $equipeObj) {
                $equipeObj->increment('pontuacao', 10);
            }
        }

        $this->salas[$pin]['resultados'][$index] = [
            'pergunta' => $questaoReal->pergunta,
            'correta' => $respostaCorretaTexto,
            'equipes' => $resEquipes
        ];

        // Busca os scores atualizados do banco após os increments acima
        $scoresAtuais = \App\Models\EquipeMultiplayer::where('partida_multiplayer_id', $pm->id)
                        ->pluck('pontuacao', 'id')->toArray();

        $this->broadcastToSala($pin, [
            'type' => 'fim_pergunta',
            'message' => $motivo,
            'correta' => $respostaCorretaTexto,
            'id_correto' => $idCorreto,
            'scores' => $scoresAtuais
        ]);

        $this->salas[$pin]['status'] = 'waiting_next';

        $this->loop->addTimer(8, function() use ($pin) {
            $pm = \App\Models\PartidaMultiplayer::with('equipes')->where('pin', $pin)->first();
            $questoes = json_decode($pm->questoes_json, true);
            $totalOriginal = count($questoes);
            
            // Incrementa o index
            $pm->increment('pergunta_atual_index');
            $novoIndex = $pm->pergunta_atual_index;

            // LÓGICA DE MORTE SÚBITA
            if ($novoIndex >= count($questoes)) {
                $maxScore = $pm->equipes->max('pontuacao');
                $vencedores = $pm->equipes->where('pontuacao', $maxScore);
                
                if ($vencedores->count() > 1) {
                    // Conta quantas rodadas de sudden death já tivemos
                    $suddenDeathCount = collect($questoes)->where('modo', 'sudden_death')->count();

                    if ($suddenDeathCount < 3) {
                        // EMPATE! Adiciona pergunta extra
                        echo "Sala $pin: Empate detectado ($maxScore pts). Ativando MORTE SÚBITA (Rodada " . ($suddenDeathCount + 1) . ")!\n";
                        
                        $pExtra = \App\Models\Questao::with('respostas')->inRandomOrder()->first();
                        
                        // Pega até 4 respostas erradas e adiciona a correta (ID 0)
                        $opcoesExtra = $pExtra->respostas->shuffle()->take(4)->pluck('id')->toArray();
                        $opcoesExtra[] = 0;
                        shuffle($opcoesExtra);

                        $novaQuestao = [
                            'id' => $pExtra->id,
                            'respAnt' => null,
                            'opcoes' => $opcoesExtra,
                            'modo' => 'sudden_death'
                        ];
                        
                        $questoes[] = $novaQuestao;
                        $pm->update(['questoes_json' => json_encode($questoes)]);
                        
                        $this->broadcastToSala($pin, [
                            'type' => 'system',
                            'message' => '🔥 EMPATE! Iniciando Morte Súbita ('.($suddenDeathCount + 1).'/3)...'
                        ]);
                    } else {
                        echo "Sala $pin: Limite de Morte Súbita atingido. Declarando Empate final!\n";
                        $this->broadcastToSala($pin, [
                            'type' => 'system',
                            'message' => '⚖️ Limite de desempates atingido. Fim de jogo com empate!'
                        ]);
                    }
                }
            }

            $this->proximaPergunta($pin);
        });
    }

    private function finalizarPartida($pin)
    {
        $pm = \App\Models\PartidaMultiplayer::with('equipes')->where('pin', $pin)->first();
        if (!$pm) return;

        $scores = $pm->equipes->pluck('pontuacao', 'id')->toArray();
        $maxScore = $pm->equipes->max('pontuacao');
        $winners = $pm->equipes->where('pontuacao', $maxScore)->pluck('id')->toArray();

        $this->broadcastToSala($pin, [
            'type' => 'finalizada',
            'message' => 'Fim de jogo!',
            'resultados_finais' => $this->salas[$pin]['resultados'] ?? [],
            'scores' => $scores,
            'winners' => $winners
        ]);
        
        $pm->update(['status' => 'finished']);
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        // Remove player from any sala they were in
        foreach ($this->salas as $pin => $sala) {
            if ($sala['conns']->contains($conn)) {
                $sala['conns']->detach($conn);
                echo "Conn {$conn->resourceId} left Sala {$pin}\n";
            }
        }
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    private function broadcastToSala($pin, $data, $except = null) {
        if (!isset($this->salas[$pin])) {
            echo "Aviso: Nenhuma conexão ativa na Sala $pin para broadcast.\n";
            return;
        }
        $count = 0;
        foreach ($this->salas[$pin]['conns'] as $client) {
            if ($client === $except) continue;
            $client->send(json_encode($data));
            $count++;
        }
        echo "Broadcast Sala $pin: Enviado para $count clientes (Excluindo remetente: " . ($except ? 'Sim' : 'Não') . ").\n";
    }
}
