<?php

namespace Tests\Feature\Lobby;

use App\Models\JogadorPartida;
use App\Services\Lobby\EntradaSalaService;
use App\Services\Lobby\EscolhaEquipeService;
use App\Services\Lobby\InicioPartidaService;
use App\Services\Lobby\LobbyException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicosLobbyTest extends TestCase
{
    use RefreshDatabase;

    public function test_escolha_de_equipe_balanceia_quando_a_diferenca_e_de_dois(): void
    {
        $partida = $this->criarSala();
        $a = $this->equipe($partida, 'Equipe A');
        $b = $this->equipe($partida, 'Equipe B');
        $this->colocarNaEquipe($partida, $a);
        $this->colocarNaEquipe($partida, $a);
        $novo = $this->criarUsuario();

        [$final, $mensagem] = app(EscolhaEquipeService::class)
            ->escolher($partida->fresh('equipes.jogadores'), $a->fresh('jogadores'), $novo->id);

        $this->assertSame($b->id, $final->id);
        $this->assertStringContainsString('Equipe B', $mensagem);
        $this->assertSame(1, JogadorPartida::where('user_id', $novo->id)->count());
    }

    public function test_escolha_de_equipe_move_o_jogador_sem_duplicar(): void
    {
        $partida = $this->criarSala();
        $a = $this->equipe($partida, 'Equipe A');
        $b = $this->equipe($partida, 'Equipe B');
        $user = $this->colocarNaEquipe($partida, $a);

        [$final, $mensagem] = app(EscolhaEquipeService::class)
            ->escolher($partida->fresh('equipes.jogadores'), $b->fresh('jogadores'), $user->id);

        $this->assertSame($b->id, $final->id);
        $this->assertNull($mensagem);
        $this->assertSame(1, JogadorPartida::where('user_id', $user->id)->count());
    }

    public function test_validacao_de_inicio_recusa_quem_nao_e_anfitriao_e_equipe_vazia(): void
    {
        $anfitriao = $this->criarUsuario();
        $partida = $this->criarSala($anfitriao);
        $servico = app(InicioPartidaService::class);

        try {
            $servico->validar($partida, $anfitriao->id + 1);
            $this->fail('Deveria recusar quem não é anfitrião');
        } catch (LobbyException $e) {
            $this->assertStringContainsString('anfitrião', $e->getMessage());
        }

        $this->expectException(LobbyException::class);
        $this->expectExceptionMessage('Ambas as equipes');
        $servico->validar($partida, $anfitriao->id);
    }

    public function test_entrada_localiza_sala_e_recusa_inexistente_ou_encerrada(): void
    {
        $this->criarSala(null, 'waiting', 'OK01');
        $this->criarSala(null, 'finished', 'FIM1');
        $servico = app(EntradaSalaService::class);

        $this->assertSame('OK01', $servico->localizar('ok01', 'encerrada')->pin);

        foreach (['FIM1' => 'encerrada', 'NAO1' => 'Sala não encontrada'] as $pin => $trecho) {
            try {
                $servico->localizar($pin, 'encerrada');
                $this->fail("PIN $pin deveria ser recusado");
            } catch (LobbyException $e) {
                $this->assertStringContainsString($trecho, $e->getMessage());
            }
        }
    }
}
