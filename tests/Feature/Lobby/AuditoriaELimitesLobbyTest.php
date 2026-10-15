<?php

namespace Tests\Feature\Lobby;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaELimitesLobbyTest extends TestCase
{
    use RefreshDatabase;

    public function test_join_e_limitado_por_ip(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->post(route('lobby.join'), ['pin' => 'ZZZZ'])->assertStatus(302);
        }

        $this->post(route('lobby.join'), ['pin' => 'ZZZZ'])->assertStatus(429);
    }

    public function test_join_guest_e_limitado_por_ip(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->post(route('lobby.joinGuest'), ['pin' => 'ZZZZ', 'nickname' => 'Ana'])->assertStatus(302);
        }

        $this->post(route('lobby.joinGuest'), ['pin' => 'ZZZZ', 'nickname' => 'Ana'])->assertStatus(429);
    }

    public function test_pin_inexistente_gera_aviso_com_ip(): void
    {
        $this->post(route('lobby.join'), ['pin' => 'ZZZZ']);

        $logs = $this->logsMultiplayer('warning');
        $this->assertSame('pin_nao_encontrado', $logs[0]['mensagem']);
        $this->assertSame('ZZZZ', $logs[0]['contexto']['pin']);
        $this->assertArrayHasKey('ip', $logs[0]['contexto']);
    }

    public function test_criar_sala_gera_log_de_auditoria(): void
    {
        $user = $this->criarUsuario();

        $this->actingAs($user)->post(route('lobby.create'))->assertRedirect();

        $this->assertContains('sala_criada', $this->eventosLogados('info'));
    }
}
