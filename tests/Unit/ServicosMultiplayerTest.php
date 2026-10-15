<?php

namespace Tests\Unit;

use App\Services\Multiplayer\ApuradorVotos;
use App\Services\Multiplayer\GerenciadorSalas;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;

class ServicosMultiplayerTest extends TestCase
{
    private function conexao(int $id): ConnectionInterface
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
        };
    }

    public function test_sem_votos_a_equipe_nao_tem_voto_final(): void
    {
        $this->assertNull(ApuradorVotos::votoDaEquipe([]));
    }

    public function test_maioria_vence_mesmo_com_voto_mais_lento(): void
    {
        $votos = [1 => ['alt' => 5, 'time' => 1.0], 2 => ['alt' => 7, 'time' => 2.0], 3 => ['alt' => 7, 'time' => 3.0]];

        $this->assertSame(7, ApuradorVotos::votoDaEquipe($votos));
    }

    public function test_empate_e_decidido_pelo_voto_mais_rapido(): void
    {
        $votos = [1 => ['alt' => 5, 'time' => 3.0], 2 => ['alt' => 7, 'time' => 1.0]];

        $this->assertSame(7, ApuradorVotos::votoDaEquipe($votos));
    }

    public function test_gerenciador_identifica_conexao_e_so_esquece_jogador_sem_outra_aba(): void
    {
        $salas = new GerenciadorSalas;
        $salas->garantir('ABCD');
        [$c1, $c2] = [$this->conexao(1), $this->conexao(2)];
        $salas->anexar('ABCD', $c1, 10);
        $salas->anexar('ABCD', $c2, 10);
        $salas->definirEquipe('ABCD', 10, 99);

        $this->assertSame(10, $salas->usuarioDaConexao('ABCD', $c1));
        $this->assertNull($salas->usuarioDaConexao('ZZZZ', $c1));

        $salas->desconectar($c1);
        $this->assertSame(99, $salas->equipeDoJogador('ABCD', 10));

        $salas->desconectar($c2);
        $this->assertNull($salas->equipeDoJogador('ABCD', 10));
    }

    public function test_gerenciador_so_conclui_a_rodada_quando_todos_conectados_votaram(): void
    {
        $salas = new GerenciadorSalas;
        $salas->garantir('ABCD');
        $salas->anexar('ABCD', $this->conexao(1), 10);
        $salas->anexar('ABCD', $this->conexao(2), 20);
        $salas->definirEquipe('ABCD', 10, 1);
        $salas->definirEquipe('ABCD', 20, 2);

        $this->assertFalse($salas->todosVotaram('ABCD'));
        $salas->registrarVoto('ABCD', 1, 10, 0);
        $this->assertFalse($salas->todosVotaram('ABCD'));
        $salas->registrarVoto('ABCD', 2, 20, 3);
        $this->assertTrue($salas->todosVotaram('ABCD'));

        $salas->novaRodada('ABCD');
        $this->assertFalse($salas->todosVotaram('ABCD'));
    }

    public function test_broadcast_respeita_o_remetente_excluido(): void
    {
        $salas = new GerenciadorSalas;
        $salas->garantir('ABCD');
        [$c1, $c2] = [$this->conexao(1), $this->conexao(2)];
        $salas->anexar('ABCD', $c1, 10);
        $salas->anexar('ABCD', $c2, 20);

        $enviados = $salas->broadcast('ABCD', ['type' => 'x'], $c1);

        $this->assertSame(1, $enviados);
        $this->assertSame([], $c1->recebidas);
        $this->assertSame([['type' => 'x']], $c2->recebidas);
    }

    public function test_revanche_exige_mais_de_uma_equipe_ativa_e_voto_de_todas(): void
    {
        $salas = new GerenciadorSalas;
        $salas->garantir('ABCD');
        $salas->definirEquipe('ABCD', 10, 1);

        $salas->registrarRevanche('ABCD', 1);
        $this->assertFalse($salas->todasAceitaramRevanche('ABCD'));

        $salas->definirEquipe('ABCD', 20, 2);
        $this->assertFalse($salas->todasAceitaramRevanche('ABCD'));

        $salas->registrarRevanche('ABCD', 2);
        $this->assertTrue($salas->todasAceitaramRevanche('ABCD'));
    }
}
