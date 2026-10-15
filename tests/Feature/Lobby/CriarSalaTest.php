<?php

namespace Tests\Feature\Lobby;

use App\Models\EquipeMultiplayer;
use App\Models\PartidaMultiplayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CriarSalaTest extends TestCase
{
    use RefreshDatabase;

    public function test_deslogado_e_redirecionado_ao_login_e_guarda_intencao(): void
    {
        $this->post(route('lobby.create'))
            ->assertRedirect(route('login'));

        $this->assertSame(route('lobby.index'), session('url.intended'));
        $this->assertTrue(session('lobby_auto_create'));
        $this->assertSame(0, PartidaMultiplayer::count());
    }

    public function test_logado_cria_sala_com_pin_e_duas_equipes_padrao(): void
    {
        $user = $this->criarUsuario();

        $resposta = $this->actingAs($user)->post(route('lobby.create'));

        $partida = PartidaMultiplayer::firstOrFail();
        $resposta->assertRedirect(route('lobby.sala', ['pin' => $partida->pin]));

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}$/', $partida->pin);
        $this->assertSame('waiting', $partida->status);
        $this->assertSame($user->id, $partida->user_id);

        $equipes = EquipeMultiplayer::where('partida_multiplayer_id', $partida->id)->orderBy('id')->get();
        $this->assertSame(['Equipe A', 'Equipe B'], $equipes->pluck('nome')->all());
        $this->assertSame(['primary', 'danger'], $equipes->pluck('cor')->all());
    }

    public function test_fluxo_de_intencao_cria_sala_automaticamente_apos_login(): void
    {
        $user = $this->criarUsuario();

        $resposta = $this->actingAs($user)
            ->withSession(['lobby_auto_create' => true])
            ->get(route('lobby.index'));

        $partida = PartidaMultiplayer::firstOrFail();
        $resposta->assertRedirect(route('lobby.sala', ['pin' => $partida->pin]));
        $this->assertNull(session('lobby_auto_create'));
    }

    public function test_index_sem_intencao_apenas_exibe_o_lobby(): void
    {
        $this->actingAs($this->criarUsuario())
            ->get(route('lobby.index'))
            ->assertOk();

        $this->assertSame(0, PartidaMultiplayer::count());
    }
}
