<?php

namespace Tests\Feature\Lobby;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutorizacaoLobbyTest extends TestCase
{
    use RefreshDatabase;

    public function test_escolher_equipe_sem_login_retorna_401(): void
    {
        $partida = $this->criarSala();

        $this->postJson(route('lobby.escolherEquipe', $partida->pin), ['equipe_id' => $this->equipe($partida, 'Equipe A')->id])
            ->assertStatus(401);
    }

    public function test_nao_escolhe_equipe_de_outra_sala(): void
    {
        $minha = $this->criarSala(null, 'waiting', 'AAAA');
        $outra = $this->criarSala(null, 'waiting', 'BBBB');
        $user = $this->criarUsuario();

        $this->actingAs($user)
            ->postJson(route('lobby.escolherEquipe', $minha->pin), ['equipe_id' => $this->equipe($outra, 'Equipe A')->id])
            ->assertStatus(404);

        $this->assertDatabaseCount('jogador_partidas', 0);
    }

    public function test_nao_escolhe_equipe_de_partida_encerrada(): void
    {
        $partida = $this->criarSala(null, 'finished');
        $user = $this->criarUsuario();

        $this->actingAs($user)
            ->postJson(route('lobby.escolherEquipe', $partida->pin), ['equipe_id' => $this->equipe($partida, 'Equipe A')->id])
            ->assertStatus(404);
    }

    public function test_equipe_id_invalido_e_rejeitado(): void
    {
        $partida = $this->criarSala();

        $this->actingAs($this->criarUsuario())
            ->postJson(route('lobby.escolherEquipe', $partida->pin), ['equipe_id' => 'abc'])
            ->assertStatus(422);
    }

    public function test_iniciar_sem_login_nao_inicia_a_partida(): void
    {
        $partida = $this->criarSala();

        $this->post(route('lobby.iniciar', $partida->pin));

        $this->assertSame('waiting', $partida->fresh()->status);
    }

    public function test_outro_usuario_logado_nao_inicia_a_partida(): void
    {
        $partida = $this->criarSala();
        $this->colocarNaEquipe($partida, $this->equipe($partida, 'Equipe A'));
        $this->colocarNaEquipe($partida, $this->equipe($partida, 'Equipe B'));

        $this->actingAs($this->criarUsuario())->post(route('lobby.iniciar', $partida->pin));

        $this->assertSame('waiting', $partida->fresh()->status);
    }

    public function test_jogar_so_existe_para_partida_em_andamento(): void
    {
        $partida = $this->criarSala();

        $this->actingAs($this->criarUsuario())
            ->get(route('lobby.jogar', $partida->pin))
            ->assertStatus(404);
    }

    public function test_dados_da_sala_inexistente_retorna_404(): void
    {
        $this->getJson(route('lobby.dados', 'ZZZZ'))->assertStatus(404);
    }
}
