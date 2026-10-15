<?php

namespace Tests\Feature\Lobby;

use App\Models\JogadorPartida;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EscolherEquipeTest extends TestCase
{
    use RefreshDatabase;

    private function escolher($user, $partida, $equipe)
    {
        return $this->actingAs($user)->postJson(
            route('lobby.escolherEquipe', ['pin' => $partida->pin]),
            ['equipe_id' => $equipe->id]
        );
    }

    public function test_deslogado_recebe_401(): void
    {
        $partida = $this->criarSala();

        $this->postJson(route('lobby.escolherEquipe', ['pin' => $partida->pin]), [
            'equipe_id' => $this->equipe($partida, 'Equipe A')->id,
        ])->assertStatus(401);
    }

    public function test_escolha_simples_registra_jogador_na_equipe(): void
    {
        $partida = $this->criarSala();
        $equipeA = $this->equipe($partida, 'Equipe A');
        $user = $this->criarUsuario();

        $this->escolher($user, $partida, $equipeA)
            ->assertOk()
            ->assertJson(['success' => true, 'mensagem' => null]);

        $this->assertDatabaseHas('jogador_partidas', [
            'user_id' => $user->id,
            'equipe_multiplayer_id' => $equipeA->id,
            'partida_multiplayer_id' => $partida->id,
        ]);
    }

    public function test_trocar_de_equipe_mantem_apenas_um_vinculo(): void
    {
        $partida = $this->criarSala();
        $equipeA = $this->equipe($partida, 'Equipe A');
        $equipeB = $this->equipe($partida, 'Equipe B');
        $user = $this->criarUsuario();

        $this->escolher($user, $partida, $equipeA)->assertOk();
        $this->escolher($user, $partida, $equipeB)->assertOk();

        $vinculos = JogadorPartida::where('user_id', $user->id)->get();
        $this->assertCount(1, $vinculos);
        $this->assertSame($equipeB->id, $vinculos->first()->equipe_multiplayer_id);
    }

    public function test_late_joiner_e_realocado_para_a_equipe_menor_quando_diferenca_e_2_ou_mais(): void
    {
        $partida = $this->criarSala();
        $equipeA = $this->equipe($partida, 'Equipe A');
        $equipeB = $this->equipe($partida, 'Equipe B');
        $this->colocarNaEquipe($partida, $equipeA);
        $this->colocarNaEquipe($partida, $equipeA);
        $this->colocarNaEquipe($partida, $equipeA);
        $this->colocarNaEquipe($partida, $equipeB);
        $novo = $this->criarUsuario();

        $this->escolher($novo, $partida, $equipeA)
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('mensagem', fn ($m) => str_contains($m, 'Equipe B'));

        $this->assertDatabaseHas('jogador_partidas', [
            'user_id' => $novo->id,
            'equipe_multiplayer_id' => $equipeB->id,
        ]);
    }

    public function test_diferenca_de_1_jogador_nao_aciona_balanceamento(): void
    {
        $partida = $this->criarSala();
        $equipeA = $this->equipe($partida, 'Equipe A');
        $equipeB = $this->equipe($partida, 'Equipe B');
        $this->colocarNaEquipe($partida, $equipeA);
        $this->colocarNaEquipe($partida, $equipeA);
        $this->colocarNaEquipe($partida, $equipeB);
        $novo = $this->criarUsuario();

        $this->escolher($novo, $partida, $equipeA)->assertOk()->assertJson(['mensagem' => null]);

        $this->assertDatabaseHas('jogador_partidas', [
            'user_id' => $novo->id,
            'equipe_multiplayer_id' => $equipeA->id,
        ]);
    }

    public function test_equipe_de_outra_partida_retorna_404(): void
    {
        $partida = $this->criarSala(null, 'waiting', 'AAAA');
        $outra = $this->criarSala(null, 'waiting', 'BBBB');

        $this->escolher($this->criarUsuario(), $partida, $this->equipe($outra, 'Equipe A'))->assertNotFound();
    }
}
