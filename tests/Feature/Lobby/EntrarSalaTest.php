<?php

namespace Tests\Feature\Lobby;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntrarSalaTest extends TestCase
{
    use RefreshDatabase;

    public function test_join_exige_pin_com_4_caracteres(): void
    {
        $this->post(route('lobby.join'), ['pin' => 'AB'])->assertSessionHasErrors('pin');
        $this->post(route('lobby.join'), [])->assertSessionHasErrors('pin');
    }

    public function test_join_com_pin_inexistente_retorna_erro(): void
    {
        $this->from(route('lobby.index'))
            ->post(route('lobby.join'), ['pin' => 'ZZZZ'])
            ->assertRedirect(route('lobby.index'))
            ->assertSessionHas('error');
    }

    public function test_join_com_partida_finalizada_retorna_erro(): void
    {
        $this->criarSala(null, 'finished', 'FIM1');

        $this->from(route('lobby.index'))
            ->post(route('lobby.join'), ['pin' => 'FIM1'])
            ->assertRedirect(route('lobby.index'))
            ->assertSessionHas('error');
    }

    public function test_join_normaliza_pin_para_maiusculas_e_redireciona_para_a_sala(): void
    {
        $this->criarSala(null, 'waiting', 'ABCD');

        $this->post(route('lobby.join'), ['pin' => 'abcd'])
            ->assertRedirect(route('lobby.sala', ['pin' => 'ABCD']));
    }

    public function test_convidado_e_criado_e_autenticado(): void
    {
        $this->criarSala(null, 'waiting', 'ABCD');

        $this->post(route('lobby.joinGuest'), ['pin' => 'abcd', 'nickname' => 'Zeca'])
            ->assertRedirect(route('lobby.sala', ['pin' => 'ABCD']));

        $convidado = User::where('is_guest', true)->firstOrFail();
        $this->assertSame('Zeca (Convidado)', $convidado->name);
        $this->assertStringStartsWith('guest_', $convidado->email);
        $this->assertStringEndsWith('@ultraquiz.com', $convidado->email);
        $this->assertAuthenticatedAs($convidado);
    }

    public function test_convidado_exige_nickname_com_ate_20_caracteres(): void
    {
        $this->criarSala(null, 'waiting', 'ABCD');

        $this->post(route('lobby.joinGuest'), ['pin' => 'ABCD'])->assertSessionHasErrors('nickname');
        $this->post(route('lobby.joinGuest'), ['pin' => 'ABCD', 'nickname' => str_repeat('a', 21)])
            ->assertSessionHasErrors('nickname');
        $this->assertSame(0, User::where('is_guest', true)->count());
    }

    public function test_convidado_em_sala_inexistente_ou_finalizada_nao_cria_usuario(): void
    {
        $this->criarSala(null, 'finished', 'FIM1');

        $this->post(route('lobby.joinGuest'), ['pin' => 'ZZZZ', 'nickname' => 'A'])->assertSessionHas('error');
        $this->post(route('lobby.joinGuest'), ['pin' => 'FIM1', 'nickname' => 'A'])->assertSessionHas('error');
        $this->assertSame(0, User::where('is_guest', true)->count());
    }

    public function test_sala_exige_login_e_guarda_url_de_retorno(): void
    {
        $this->criarSala(null, 'waiting', 'ABCD');

        $this->get(route('lobby.sala', ['pin' => 'ABCD']))->assertRedirect(route('login'));
        $this->assertSame(route('lobby.sala', ['pin' => 'ABCD']), session('url.intended'));
    }

    public function test_sala_aceita_waiting_e_rejeita_finished_ou_inexistente(): void
    {
        $user = $this->criarUsuario();
        $this->criarSala(null, 'waiting', 'ABCD');
        $this->criarSala(null, 'finished', 'FIM1');

        $this->actingAs($user)->get(route('lobby.sala', ['pin' => 'ABCD']))->assertOk();
        $this->actingAs($user)->get(route('lobby.sala', ['pin' => 'FIM1']))->assertNotFound();
        $this->actingAs($user)->get(route('lobby.sala', ['pin' => 'ZZZZ']))->assertNotFound();
    }

    public function test_jogador_ja_em_equipe_e_enviado_direto_para_o_jogo_quando_playing(): void
    {
        $partida = $this->criarSala(null, 'playing', 'ABCD');
        $jogador = $this->colocarNaEquipe($partida, $this->equipe($partida, 'Equipe A'));

        $this->actingAs($jogador)->get(route('lobby.sala', ['pin' => 'ABCD']))
            ->assertRedirect(route('lobby.jogar', ['pin' => 'ABCD']));
    }

    public function test_late_joiner_sem_equipe_ve_o_lobby_mesmo_com_partida_playing(): void
    {
        $this->criarSala(null, 'playing', 'ABCD');

        $this->actingAs($this->criarUsuario())->get(route('lobby.sala', ['pin' => 'ABCD']))->assertOk();
    }

    public function test_sala_e_jogo_entregam_token_websocket_valido_do_proprio_usuario(): void
    {
        $partida = $this->criarSala(null, 'playing', 'ABCD');
        $jogador = $this->colocarNaEquipe($partida, $this->equipe($partida, 'Equipe A'));
        $espectador = $this->criarUsuario();

        foreach ([[$jogador, 'lobby.jogar'], [$espectador, 'lobby.sala']] as [$user, $rota]) {
            $html = $this->actingAs($user)->get(route($rota, ['pin' => 'ABCD']))->assertOk()->getContent();

            $this->assertSame(1, preg_match('/const wsToken = "([^"]+)"/', $html, $m));
            $this->assertSame($user->id, \App\Support\WebSocketToken::validar(html_entity_decode($m[1]), 'ABCD'));
        }
    }
}
