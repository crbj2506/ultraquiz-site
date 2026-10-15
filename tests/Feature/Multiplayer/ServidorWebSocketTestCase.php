<?php

namespace Tests\Feature\Multiplayer;

use App\Console\Commands\WebSocketServer;
use App\Models\PartidaMultiplayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ratchet\ConnectionInterface;
use Tests\TestCase;

/**
 * Base para testes de caracterização do servidor WebSocket: usa o servidor real,
 * com conexões e loop de eventos falsos (nenhuma porta é aberta).
 */
abstract class ServidorWebSocketTestCase extends TestCase
{
    use RefreshDatabase;

    protected WebSocketServer $servidor;
    protected object $loop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loop = new class {
            public array $timers = [];
            public array $cancelados = [];
            private int $seq = 0;

            public function addTimer($intervalo, callable $cb)
            {
                $this->timers[++$this->seq] = ['intervalo' => $intervalo, 'cb' => $cb, 'periodico' => false];
                return $this->seq;
            }

            public function addPeriodicTimer($intervalo, callable $cb)
            {
                $this->timers[++$this->seq] = ['intervalo' => $intervalo, 'cb' => $cb, 'periodico' => true];
                return $this->seq;
            }

            public function cancelTimer($id): void
            {
                $this->cancelados[] = $id;
                unset($this->timers[$id]);
            }

            /** Dispara o primeiro timer único (o intervalo entre perguntas) e o remove. */
            public function dispararTimerUnico(): void
            {
                foreach ($this->timers as $id => $t) {
                    if (!$t['periodico']) {
                        unset($this->timers[$id]);
                        ($t['cb'])();
                        return;
                    }
                }
                throw new \RuntimeException('Nenhum timer único pendente.');
            }
        };

        $this->servidor = new WebSocketServer();
        $this->definirPropriedade('loop', $this->loop);
    }

    protected function definirPropriedade(string $nome, $valor): void
    {
        $p = new \ReflectionProperty(WebSocketServer::class, $nome);
        $p->setValue($this->servidor, $valor);
    }

    protected function lerPropriedade(string $nome)
    {
        return (new \ReflectionProperty(WebSocketServer::class, $nome))->getValue($this->servidor);
    }

    protected function chamarPrivado(string $metodo, ...$args)
    {
        ob_start();
        try {
            return (new \ReflectionMethod(WebSocketServer::class, $metodo))->invoke($this->servidor, ...$args);
        } finally {
            ob_end_clean();
        }
    }

    protected function enviar(ConnectionInterface $conn, array $msg): void
    {
        ob_start();
        try {
            $this->servidor->onMessage($conn, json_encode($msg));
        } finally {
            ob_end_clean();
        }
    }

    protected function conexao(int $id): object
    {
        return new class($id) implements ConnectionInterface {
            public array $recebidas = [];

            public function __construct(public int $resourceId)
            {
            }

            public function send($data)
            {
                $this->recebidas[] = json_decode($data, true);
                return $this;
            }

            public function close(): void
            {
            }

            public function tipos(): array
            {
                return array_column($this->recebidas, 'type');
            }

            public function ultima(string $tipo): ?array
            {
                $msgs = array_values(array_filter($this->recebidas, fn ($m) => ($m['type'] ?? null) === $tipo));
                return $msgs ? end($msgs) : null;
            }
        };
    }

    /** Cria sala 'playing' com N questões; em cada uma a resposta correta é a opção de id 0. */
    protected function salaEmAndamento(int $totalQuestoes = 1, string $pin = 'ABCD'): PartidaMultiplayer
    {
        $this->criarQuestoesAprovadas($totalQuestoes);
        $partida = $this->criarSala(null, 'playing', $pin);

        $questoes = \App\Models\Questao::with('respostas')->orderBy('id')->get()->map(fn ($q) => [
            'id' => $q->id,
            'respAnt' => null,
            'opcoes' => array_merge([0], $q->respostas->take(4)->pluck('id')->all()),
        ])->all();

        $partida->update(['questoes_json' => json_encode($questoes), 'pergunta_atual_index' => 0]);

        return $partida->fresh();
    }

    /** Prepara o estado em memória da sala, como se os jogadores já tivessem entrado. */
    protected function prepararEstadoSala(string $pin, array $playerEquipe, array $votosDetalhados): void
    {
        $this->definirPropriedade('salas', [
            $pin => [
                'conns' => new \SplObjectStorage(),
                'votos' => [],
                'votos_detalhados' => $votosDetalhados,
                'votos_jogadores' => [],
                'player_equipe' => $playerEquipe,
                'resultados' => [],
                'rematch_votos' => [],
                'status' => 'playing',
            ],
        ]);
    }
}
