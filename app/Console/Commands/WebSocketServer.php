<?php

namespace App\Console\Commands;

use App\Models\JogadorPartida;
use App\Models\PartidaMultiplayer;
use App\Services\Multiplayer\ApuracaoService;
use App\Services\Multiplayer\GerenciadorSalas;
use App\Services\Multiplayer\PerguntaService;
use App\Support\WebSocketToken;
use Illuminate\Console\Command;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

/**
 * Servidor WebSocket do multiplayer: recebe as mensagens, aciona os serviços
 * (estado das salas, perguntas, apuração) e controla os timers das rodadas.
 */
class WebSocketServer extends Command implements MessageComponentInterface
{
    protected $signature = 'websocket:serve {--port=8090}';
    protected $description = 'Start the WebSocket server for UltraQuiz Multiplayer';

    protected $clients;
    protected GerenciadorSalas $salas;
    protected PerguntaService $perguntas;
    protected ApuracaoService $apuracao;
    protected $loop;

    public function __construct()
    {
        parent::__construct();
        $this->clients = new \SplObjectStorage;
        $this->salas = new GerenciadorSalas;
        $this->perguntas = new PerguntaService;
        $this->apuracao = new ApuracaoService;
    }

    public function handle()
    {
        $port = $this->option('port');

        $server = IoServer::factory(
            new HttpServer(new WsServer($this)),
            $port,
            '0.0.0.0'
        );

        $this->info("WebSocket Server started on port {$port} explicitly bound to all interfaces (0.0.0.0)");
        $this->loop = $server->loop;
        $server->run();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->offsetSet($conn, null);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        try {
            $data = json_decode($msg, true);
            if (!$data || !isset($data['action'])) return;

            $action = $data['action'];
            $pin = isset($data['pin']) ? strtoupper(trim($data['pin'])) : null;

            $userId = $this->identificar($from, $action, $pin, $data);
            if ($userId === null) return;

            echo "Mensagem recebida: Action={$action}, PIN={$pin}, User={$userId}\n";

            switch ($action) {
                case 'join':
                    $this->entrar($from, $pin, $userId);
                    break;
                case 'votar':
                    if (isset($data['alternativa_id'])) {
                        $this->votar($pin, $userId, $data['alternativa_id']);
                    }
                    break;
                case 'start':
                    $this->iniciar($pin, $userId);
                    break;
                case 'rematch_vote':
                    $this->votarRevanche($pin, $userId);
                    break;
            }
        } catch (\Exception $e) {
            echo "Erro em onMessage: " . $e->getMessage() . "\n";
        }
    }

    /**
     * A identidade nunca vem do cliente: no join vem do token assinado,
     * nas demais ações vem da conexão que já passou pelo join.
     */
    private function identificar(ConnectionInterface $from, string $action, ?string $pin, array $data): ?int
    {
        if ($action === 'join') {
            $userId = WebSocketToken::validar($data['token'] ?? null, $pin);
            if ($userId === null) {
                echo "[AUTH] Join recusado (token ausente, inválido ou expirado). Conn {$from->resourceId}\n";
                $from->send(json_encode(['type' => 'auth_error', 'message' => 'Sessão inválida. Recarregue a página.']));
            }

            return $userId;
        }

        $userId = $pin ? $this->salas->usuarioDaConexao($pin, $from) : null;
        if ($userId === null) {
            echo "[AUTH] Ação '$action' ignorada: conexão {$from->resourceId} não autenticada na sala.\n";
        }

        return $userId;
    }

    private function entrar(ConnectionInterface $from, ?string $pin, int $userId): void
    {
        if (!$pin) return;

        $this->salas->garantir($pin);
        $this->salas->anexar($pin, $from, $userId);
        echo "User $userId joined/refreshed Sala $pin (Conn {$from->resourceId})\n";

        $jogador = JogadorPartida::where('user_id', $userId)
            ->whereHas('partida', fn ($q) => $q->where('pin', $pin))
            ->first();
        if ($jogador) {
            $this->salas->definirEquipe($pin, $userId, $jogador->equipe_multiplayer_id);
        }

        $this->salas->broadcast($pin, [
            'type' => 'system',
            'user_id' => $userId,
            'message' => 'Um jogador entrou.',
        ], $from);

        $this->sincronizarEntrada($pin, $from);
    }

    /** Quem entra numa partida em andamento dispara a primeira rodada ou recebe a pergunta atual. */
    private function sincronizarEntrada(string $pin, ConnectionInterface $from): void
    {
        $pm = PartidaMultiplayer::where('pin', $pin)->first();
        if (!$pm || $pm->status !== 'playing') {
            return;
        }

        $status = $this->salas->get($pin, 'status', 'idle');
        if ($status !== 'idle' && $status !== 'playing') {
            return; // em intervalo: o timer pendente avança sozinho
        }

        if ($this->salas->get($pin, 'timer') === null) {
            $this->proximaPergunta($pin);
        } else {
            $this->enviarPerguntaAtual($pm, $from);
        }
    }

    private function votar(string $pin, int $userId, $alternativaId): void
    {
        // Votos só valem enquanto a rodada está aberta (não no intervalo entre perguntas).
        if ($this->salas->get($pin, 'status') !== 'playing') return;

        $equipeId = $this->salas->equipeDoJogador($pin, $userId);
        if (!$equipeId) return;

        $pm = PartidaMultiplayer::where('pin', $pin)->first();
        if (!$this->perguntas->alternativaValida($pm, $alternativaId)) {
            echo "[VOTAR] Alternativa inválida ignorada. Sala: $pin | User: $userId\n";
            return;
        }

        $this->salas->registrarVoto($pin, $equipeId, $userId, $alternativaId);
        echo "[VOTAR] Sala: $pin | User: $userId | Equipe: $equipeId | Alt: $alternativaId\n";

        $this->salas->broadcast($pin, [
            'type' => 'voto',
            'user_id' => $userId,
            'equipe_id' => $equipeId,
            'alternativa_id' => $alternativaId,
        ]);

        if ($this->salas->todosVotaram($pin)) {
            echo "Todos os jogadores conectados votaram na Sala $pin. Pulando timer!\n";
            $this->processarFimDaPergunta($pin, 'Todos votaram!');
        }
    }

    private function iniciar(string $pin, int $userId): void
    {
        $anfitriaoId = PartidaMultiplayer::where('pin', $pin)->value('user_id');
        if ((int) $anfitriaoId !== $userId) {
            echo "[AUTH] START recusado: User $userId não é o anfitrião da Sala $pin.\n";
            return;
        }

        echo "Comando START recebido para Sala $pin. Redirecionando todos para a tela de jogo...\n";
        $this->salas->set($pin, 'status', 'playing');
        $this->salas->broadcast($pin, ['type' => 'redirect']);
    }

    private function votarRevanche(string $pin, int $userId): void
    {
        $equipeId = $this->salas->equipeDoJogador($pin, $userId);
        if (!$equipeId) return;

        // Revanche só depois do fim da partida; antes disso reiniciaria um jogo em andamento.
        if (PartidaMultiplayer::where('pin', $pin)->value('status') !== 'finished') return;

        echo "Voto de revanche recebido: Sala $pin, Equipe $equipeId\n";
        $this->salas->registrarRevanche($pin, $equipeId);

        if (!$this->salas->todasAceitaramRevanche($pin)) {
            $votaram = $this->salas->equipesQueVotaramRevanche($pin);
            $this->salas->broadcast($pin, [
                'type' => 'rematch_status',
                'equipes_que_votaram' => $votaram,
                'aguardando' => array_values(array_diff($this->salas->equipesAtivas($pin), $votaram)),
            ]);
            return;
        }

        $pm = PartidaMultiplayer::with('equipes')->where('pin', $pin)->first();
        if (!$pm) return;

        echo "Revanche aceita por todas as equipes na Sala $pin! Reiniciando...\n";
        $partida = new \App\Models\Partida();
        $partida->criar();
        $questoes = array_slice($partida->getState()['questoes_data'], 0, PartidaMultiplayer::quantidadeQuestoes());

        $pm->update(['status' => 'playing', 'questoes_json' => json_encode($questoes), 'pergunta_atual_index' => 0]);
        foreach ($pm->equipes as $equipe) {
            $equipe->update(['pontuacao' => 0]);
        }

        $this->salas->reiniciarParaRevanche($pin);
        $this->salas->broadcast($pin, ['type' => 'reset']);
        $this->proximaPergunta($pin);
    }

    private function proximaPergunta(string $pin): void
    {
        $pm = PartidaMultiplayer::where('pin', $pin)->first();
        if (!$pm || !$pm->questoes_json) {
            echo "Erro: Partida ou questões não encontradas para Sala $pin\n";
            return;
        }

        $questoes = $this->perguntas->questoes($pm);
        $index = $pm->pergunta_atual_index;

        if (!$questoes || $index >= count($questoes)) {
            echo "Partida finalizada ou sem questões para Sala $pin\n";
            $this->finalizarPartida($pin);
            return;
        }

        $this->salas->novaRodada($pin);
        echo "Enviando Pergunta #" . ($index + 1) . " para Sala $pin\n";
        $this->salas->broadcast($pin, $this->perguntas->dados($pm, $questoes, $index));

        $this->iniciarTimer($pin, PerguntaService::TEMPO_RODADA);
    }

    private function enviarPerguntaAtual(PartidaMultiplayer $pm, ConnectionInterface $conn): void
    {
        $questoes = $this->perguntas->questoes($pm);
        $index = $pm->pergunta_atual_index;
        if ($index >= count($questoes)) return;

        $dados = $this->perguntas->dados($pm, $questoes, $index);

        // Se houver um timer rodando, manda o tempo REAL restante
        $restante = $this->salas->get($pm->pin, 'tempo_restante');
        if ($restante !== null) {
            $dados['tempo'] = $restante;
        }

        $conn->send(json_encode($dados));
        echo "Sincronizando Pergunta #" . ($index + 1) . " com novo cliente na Sala {$pm->pin}\n";
    }

    private function iniciarTimer(string $pin, int $segundos): void
    {
        $this->cancelarTimer($pin);

        $this->salas->set($pin, 'tempo_restante', $segundos);
        $timer = $this->loop->addPeriodicTimer(1, function () use ($pin) {
            $restante = $this->salas->get($pin, 'tempo_restante') - 1;
            $this->salas->set($pin, 'tempo_restante', $restante);

            $this->salas->broadcast($pin, ['type' => 'timer', 'tempo' => $restante]);

            if ($restante <= 0) {
                $this->processarFimDaPergunta($pin);
            }
        });
        $this->salas->set($pin, 'timer', $timer);
    }

    private function cancelarTimer(string $pin): void
    {
        $timer = $this->salas->get($pin, 'timer');
        if ($timer !== null) {
            $this->loop->cancelTimer($timer);
            $this->salas->remover($pin, 'timer');
        }
    }

    private function processarFimDaPergunta(string $pin, string $motivo = 'Tempo esgotado!'): void
    {
        // Idempotente: só uma apuração por rodada, mesmo com timer e último voto chegando juntos.
        if ($this->salas->get($pin, 'status') !== 'playing') return;
        $this->salas->set($pin, 'status', 'waiting_next');

        $this->cancelarTimer($pin);

        $pm = PartidaMultiplayer::where('pin', $pin)->first();
        $questoes = $this->perguntas->questoes($pm);
        $index = $pm->pergunta_atual_index;

        $apuracao = $this->apuracao->apurar(
            $pm,
            $questoes[$index],
            $this->salas->equipesDaRodada($pin),
            $this->salas->get($pin, 'votos_detalhados', [])
        );
        $questao = $apuracao['questao'];

        $resultados = $this->salas->get($pin, 'resultados', []);
        $resultados[$index] = [
            'pergunta' => $questao->pergunta,
            'correta' => $questao->resposta,
            'equipes' => $apuracao['equipes'],
        ];
        $this->salas->set($pin, 'resultados', $resultados);

        $this->salas->broadcast($pin, [
            'type' => 'fim_pergunta',
            'message' => $motivo,
            'correta' => $questao->resposta,
            'id_correto' => ApuracaoService::ID_RESPOSTA_CORRETA,
            'scores' => $apuracao['scores'],
        ]);

        $this->loop->addTimer(PerguntaService::INTERVALO_ENTRE_PERGUNTAS, fn () => $this->avancarPergunta($pin));
    }

    /** Passa para a próxima pergunta; se acabaram e há empate, ativa a morte súbita. */
    private function avancarPergunta(string $pin): void
    {
        $pm = PartidaMultiplayer::with('equipes')->where('pin', $pin)->first();
        $questoes = $this->perguntas->questoes($pm);

        $pm->increment('pergunta_atual_index');

        if ($pm->pergunta_atual_index >= count($questoes)) {
            $maxima = $pm->equipes->max('pontuacao');

            if ($pm->equipes->where('pontuacao', $maxima)->count() > 1) {
                $this->resolverEmpate($pin, $pm, $questoes, $maxima);
            }
        }

        $this->proximaPergunta($pin);
    }

    private function resolverEmpate(string $pin, PartidaMultiplayer $pm, array $questoes, $pontuacao): void
    {
        $rodadas = $this->perguntas->rodadasDeMorteSubita($questoes);

        if ($rodadas >= PerguntaService::MAX_MORTE_SUBITA) {
            echo "Sala $pin: Limite de Morte Súbita atingido. Declarando Empate final!\n";
            $this->salas->broadcast($pin, [
                'type' => 'system',
                'message' => '⚖️ Limite de desempates atingido. Fim de jogo com empate!',
            ]);
            return;
        }

        $extra = $this->perguntas->novaQuestaoDeMorteSubita($questoes);
        if (!$extra) {
            echo "Sala $pin: Sem questões disponíveis para a Morte Súbita. Declarando Empate final!\n";
            $this->salas->broadcast($pin, [
                'type' => 'system',
                'message' => '⚖️ Não há mais perguntas para desempate. Fim de jogo com empate!',
            ]);
            return;
        }

        echo "Sala $pin: Empate detectado ($pontuacao pts). Ativando MORTE SÚBITA (Rodada " . ($rodadas + 1) . ")!\n";
        $questoes[] = $extra;
        $pm->update(['questoes_json' => json_encode($questoes)]);

        $this->salas->broadcast($pin, [
            'type' => 'system',
            'message' => '🔥 EMPATE! Iniciando Morte Súbita (' . ($rodadas + 1) . '/' . PerguntaService::MAX_MORTE_SUBITA . ')...',
        ]);
    }

    private function finalizarPartida(string $pin): void
    {
        $pm = PartidaMultiplayer::with('equipes')->where('pin', $pin)->first();
        if (!$pm) return;

        $maxima = $pm->equipes->max('pontuacao');

        $this->salas->broadcast($pin, [
            'type' => 'finalizada',
            'message' => 'Fim de jogo!',
            'resultados_finais' => $this->salas->get($pin, 'resultados', []),
            'scores' => $pm->equipes->pluck('pontuacao', 'id')->toArray(),
            'winners' => $pm->equipes->where('pontuacao', $maxima)->pluck('id')->toArray(),
        ]);

        $pm->update(['status' => 'finished']);
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->offsetUnset($conn);
        $this->salas->desconectar($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
