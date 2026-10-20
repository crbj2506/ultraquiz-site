<?php

namespace App\Console\Commands;

use App\Models\JogadorPartida;
use App\Models\PartidaMultiplayer;
use App\Services\Multiplayer\ApuracaoService;
use App\Services\Multiplayer\GerenciadorSalas;
use App\Services\Multiplayer\PerguntaService;
use App\Support\LogMultiplayer as Log;
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
    private const ACOES = ['join', 'votar', 'start', 'rematch_vote'];
    private const MAX_BYTES_MENSAGEM = 2048;

    protected $signature = 'websocket:serve {--host=0.0.0.0} {--port=8090}';
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
        $host = $this->option('host');
        $port = $this->option('port');

        $server = IoServer::factory(
            new HttpServer(new WsServer($this)),
            $port,
            $host
        );

        $this->info("WebSocket Server started on {$host}:{$port}");
        Log::info('servidor_iniciado', ['host' => $host, 'porta' => $port]);
        $this->loop = $server->loop;
        $server->run();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->offsetSet($conn, null);
        Log::debug('conexao_aberta', ['conn' => $conn->resourceId]);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $pin = null;

        try {
            $entrada = $this->validarMensagem($from, $msg);
            if ($entrada === null) return;

            [$action, $pin, $data] = $entrada;

            $userId = $this->identificar($from, $action, $pin, $data);
            if ($userId === null) return;

            Log::debug('mensagem_recebida', ['acao' => $action, 'pin' => $pin, 'user_id' => $userId]);

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
        } catch (\Throwable $e) {
            Log::erro('erro_ao_processar_mensagem', $e, ['pin' => $pin, 'conn' => $from->resourceId]);
            $from->send(json_encode([
                'type' => 'erro',
                'message' => 'Não foi possível processar sua ação. Tente novamente.',
            ]));
        }
    }

    /** @return array{0: string, 1: string, 2: array}|null ação, PIN normalizado e dados; null se inválida */
    private function validarMensagem(ConnectionInterface $from, $msg): ?array
    {
        $recusar = function (string $motivo) use ($from) {
            Log::aviso('mensagem_invalida', ['motivo' => $motivo, 'conn' => $from->resourceId]);
            return null;
        };

        if (!is_string($msg) || strlen($msg) > self::MAX_BYTES_MENSAGEM) {
            return $recusar('tamanho');
        }

        $data = json_decode($msg, true);
        if (!is_array($data)) {
            return $recusar('json');
        }

        $action = $data['action'] ?? null;
        if (!is_string($action) || !in_array($action, self::ACOES, true)) {
            return $recusar('acao');
        }

        $pin = $data['pin'] ?? null;
        if (!is_string($pin) || !preg_match('/^[A-Za-z0-9]{4}$/', trim($pin))) {
            return $recusar('pin');
        }

        return [$action, strtoupper(trim($pin)), $data];
    }

    /**
     * A identidade nunca vem do cliente: no join vem do token assinado,
     * nas demais ações vem da conexão que já passou pelo join.
     */
    private function identificar(ConnectionInterface $from, string $action, string $pin, array $data): ?int
    {
        if ($action === 'join') {
            $token = $data['token'] ?? null;
            $userId = is_string($token) ? WebSocketToken::validar($token, $pin) : null;
            if ($userId === null) {
                Log::aviso('join_recusado', ['pin' => $pin, 'conn' => $from->resourceId]);
                $from->send(json_encode(['type' => 'auth_error', 'message' => 'Sessão inválida. Recarregue a página.']));
            }

            return $userId;
        }

        $userId = $this->salas->usuarioDaConexao($pin, $from);
        if ($userId === null) {
            Log::aviso('acao_nao_autenticada', ['acao' => $action, 'pin' => $pin, 'conn' => $from->resourceId]);
        }

        return $userId;
    }

    private function entrar(ConnectionInterface $from, string $pin, int $userId): void
    {
        $this->salas->garantir($pin);
        $this->salas->anexar($pin, $from, $userId);

        $jogador = JogadorPartida::where('user_id', $userId)
            ->whereHas('partida', fn ($q) => $q->where('pin', $pin))
            ->first();
        if ($jogador) {
            $this->salas->definirEquipe($pin, $userId, $jogador->equipe_multiplayer_id);
        }

        Log::info('jogador_entrou', [
            'pin' => $pin,
            'user_id' => $userId,
            'equipe_id' => $jogador?->equipe_multiplayer_id,
            'conn' => $from->resourceId,
        ]);

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
            Log::aviso('voto_invalido', ['pin' => $pin, 'user_id' => $userId]);
            return;
        }

        $this->salas->registrarVoto($pin, $equipeId, $userId, $alternativaId);
        Log::info('voto_registrado', ['pin' => $pin, 'user_id' => $userId, 'equipe_id' => $equipeId]);

        $this->salas->broadcast($pin, [
            'type' => 'voto',
            'user_id' => $userId,
            'equipe_id' => $equipeId,
            'alternativa_id' => $alternativaId,
        ]);

        if ($this->salas->todosVotaram($pin)) {
            $this->processarFimDaPergunta($pin, 'Todos votaram!');
        }
    }

    private function iniciar(string $pin, int $userId): void
    {
        $anfitriaoId = PartidaMultiplayer::where('pin', $pin)->value('user_id');
        if ((int) $anfitriaoId !== $userId) {
            Log::aviso('start_recusado', ['pin' => $pin, 'user_id' => $userId]);
            return;
        }

        Log::info('start_recebido', ['pin' => $pin, 'user_id' => $userId]);
        $this->salas->set($pin, 'status', 'playing');
        $this->salas->broadcast($pin, ['type' => 'redirect']);
    }

    private function votarRevanche(string $pin, int $userId): void
    {
        $equipeId = $this->salas->equipeDoJogador($pin, $userId);
        if (!$equipeId) return;

        // Revanche só depois do fim da partida; antes disso reiniciaria um jogo em andamento.
        if (PartidaMultiplayer::where('pin', $pin)->value('status') !== 'finished') return;

        Log::info('revanche_voto', ['pin' => $pin, 'user_id' => $userId, 'equipe_id' => $equipeId]);
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

        $partida = new \App\Models\Partida();
        $partida->criar(PartidaMultiplayer::quantidadeQuestoes());
        $questoes = $partida->getState()['questoes_data'];

        $pm->update(['status' => 'playing', 'questoes_json' => json_encode($questoes), 'pergunta_atual_index' => 0]);
        foreach ($pm->equipes as $equipe) {
            $equipe->update(['pontuacao' => 0]);
        }

        Log::info('revanche_iniciada', ['pin' => $pin, 'questoes' => count($questoes)]);
        $this->salas->reiniciarParaRevanche($pin);
        $this->salas->broadcast($pin, ['type' => 'reset']);
        $this->proximaPergunta($pin);
    }

    private function proximaPergunta(string $pin): void
    {
        $pm = PartidaMultiplayer::where('pin', $pin)->first();
        if (!$pm || !$pm->questoes_json) {
            Log::aviso('partida_sem_questoes', ['pin' => $pin]);
            return;
        }

        $questoes = $this->perguntas->questoes($pm);
        $index = $pm->pergunta_atual_index;

        if (!$questoes || $index >= count($questoes)) {
            $this->finalizarPartida($pin);
            return;
        }

        $this->salas->novaRodada($pin);
        Log::info('rodada_iniciada', ['pin' => $pin, 'pergunta' => $index + 1, 'total' => count($questoes)]);
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
        Log::debug('pergunta_sincronizada', ['pin' => $pm->pin, 'pergunta' => $index + 1, 'conn' => $conn->resourceId]);
    }

    private function iniciarTimer(string $pin, int $segundos): void
    {
        $this->cancelarTimer($pin);

        $this->salas->set($pin, 'tempo_restante', $segundos);
        $timer = $this->loop->addPeriodicTimer(1, $this->protegido($pin, function () use ($pin) {
            $restante = $this->salas->get($pin, 'tempo_restante') - 1;
            $this->salas->set($pin, 'tempo_restante', $restante);

            $this->salas->broadcast($pin, ['type' => 'timer', 'tempo' => $restante]);

            if ($restante <= 0) {
                $this->processarFimDaPergunta($pin);
            }
        }));
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

    /**
     * Callbacks de timer rodam fora do onMessage: uma exceção ali derrubaria o servidor de todas as salas.
     * Em caso de erro, loga e devolve a sala ao estado "idle", para o próximo join retomar a rodada.
     */
    private function protegido(string $pin, callable $fn): callable
    {
        return function () use ($pin, $fn) {
            try {
                $fn();
            } catch (\Throwable $e) {
                Log::erro('erro_no_timer', $e, ['pin' => $pin]);
                $this->cancelarTimer($pin);
                $this->salas->set($pin, 'status', 'idle');
                $this->salas->broadcast($pin, [
                    'type' => 'erro',
                    'message' => 'Ocorreu um erro na sala. Recarregue a página para continuar.',
                ]);
            }
        };
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

        Log::info('rodada_apurada', [
            'pin' => $pin,
            'pergunta' => $index + 1,
            'motivo' => $motivo,
            'scores' => $apuracao['scores'],
        ]);

        $this->salas->broadcast($pin, [
            'type' => 'fim_pergunta',
            'message' => $motivo,
            'correta' => $questao->resposta,
            'id_correto' => ApuracaoService::ID_RESPOSTA_CORRETA,
            'scores' => $apuracao['scores'],
        ]);

        $this->loop->addTimer(
            PerguntaService::INTERVALO_ENTRE_PERGUNTAS,
            $this->protegido($pin, fn () => $this->avancarPergunta($pin))
        );
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
            Log::info('empate_final', ['pin' => $pin, 'motivo' => 'limite_de_desempates']);
            $this->salas->broadcast($pin, [
                'type' => 'system',
                'message' => '⚖️ Limite de desempates atingido. Fim de jogo com empate!',
            ]);
            return;
        }

        $extra = $this->perguntas->novaQuestaoDeMorteSubita($questoes);
        if (!$extra) {
            Log::aviso('empate_final', ['pin' => $pin, 'motivo' => 'sem_questoes_disponiveis']);
            $this->salas->broadcast($pin, [
                'type' => 'system',
                'message' => '⚖️ Não há mais perguntas para desempate. Fim de jogo com empate!',
            ]);
            return;
        }

        Log::info('morte_subita_ativada', ['pin' => $pin, 'rodada' => $rodadas + 1, 'pontuacao' => $pontuacao]);
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
        $vencedoras = $pm->equipes->where('pontuacao', $maxima)->pluck('id')->toArray();
        $placar = $pm->equipes->pluck('pontuacao', 'id')->toArray();

        Log::info('partida_finalizada', ['pin' => $pin, 'vencedoras' => $vencedoras, 'scores' => $placar]);

        $this->salas->broadcast($pin, [
            'type' => 'finalizada',
            'message' => 'Fim de jogo!',
            'resultados_finais' => $this->salas->get($pin, 'resultados', []),
            'scores' => $placar,
            'winners' => $vencedoras,
        ]);

        $pm->update(['status' => 'finished']);
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->offsetUnset($conn);
        $this->salas->desconectar($conn);
        Log::debug('conexao_fechada', ['conn' => $conn->resourceId]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Log::erro('erro_de_conexao', $e, ['conn' => $conn->resourceId]);
        $conn->close();
    }
}
