<?php

namespace Tests\Feature\Multiplayer;

use Illuminate\Support\Facades\DB;

class RobustezWebSocketTest extends ServidorWebSocketTestCase
{
    private function conectado(): array
    {
        $partida = $this->salaEmAndamento(2);
        $a = $this->equipe($partida, 'Equipe A');
        $b = $this->equipe($partida, 'Equipe B');
        $u1 = $this->colocarNaEquipe($partida, $a);
        $u2 = $this->colocarNaEquipe($partida, $b);
        $c1 = $this->conexao(1);
        $c2 = $this->conexao(2);
        $this->entrar($c1, $u1);
        $this->entrar($c2, $u2);

        return [$partida, $u1, $u2, $c1, $c2];
    }

    public function test_erro_no_timer_do_intervalo_nao_derruba_o_servidor_e_e_logado(): void
    {
        [$partida, , , $c1] = $this->conectado();
        $this->chamarPrivado('processarFimDaPergunta', 'ABCD');

        // A partida some do banco antes do timer de 8s disparar.
        DB::table('partida_multiplayers')->where('id', $partida->id)->delete();

        $this->loop->dispararTimerUnico();

        $this->assertContains('erro_no_timer', $this->eventosLogados('error'));
    }

    public function test_erro_no_timer_da_rodada_nao_derruba_o_servidor_e_e_logado(): void
    {
        [$partida] = $this->conectado();
        DB::table('partida_multiplayers')->where('id', $partida->id)->delete();

        $periodico = collect($this->loop->timers)->first(fn ($t) => $t['periodico']);
        for ($i = 0; $i < 61; $i++) {
            ($periodico['cb'])();
        }

        $this->assertContains('erro_no_timer', $this->eventosLogados('error'));
    }

    public function test_excecao_ao_processar_mensagem_avisa_o_cliente_e_e_logada(): void
    {
        $partida = $this->salaEmAndamento(1);
        $u1 = $this->colocarNaEquipe($partida, $this->equipe($partida, 'Equipe A'));
        $c1 = $this->conexao(1);

        // A questão some do banco: montar a primeira pergunta falha.
        DB::table('respostas')->delete();
        DB::table('questoes')->delete();

        $this->entrar($c1, $u1);

        $this->assertContains('erro_ao_processar_mensagem', $this->eventosLogados('error'));
        $this->assertSame('erro', $c1->ultima('erro')['type']);
        $this->assertStringNotContainsString('questoes', json_encode($c1->ultima('erro')));
    }

    public function test_payloads_invalidos_sao_ignorados_e_logados_como_aviso(): void
    {
        $c = $this->conexao(9);

        $this->servidor->onMessage($c, 'isso nao e json');
        $this->servidor->onMessage($c, json_encode(['action' => 'explodir', 'pin' => 'ABCD']));
        $this->servidor->onMessage($c, json_encode(['action' => 'join', 'pin' => 'ABCDE', 'token' => 'x']));
        $this->servidor->onMessage($c, json_encode(['action' => 'join', 'pin' => ['ABCD'], 'token' => 'x']));
        $this->servidor->onMessage($c, str_repeat('a', 5000));

        $this->assertSame([], $c->recebidas);
        $this->assertCount(5, $this->logsMultiplayer('warning'));
    }

    public function test_eventos_principais_ficam_registrados_com_contexto(): void
    {
        [$partida, $u1, , $c1] = $this->conectado();

        $this->enviar($c1, ['action' => 'votar', 'pin' => 'ABCD', 'alternativa_id' => 0]);
        $this->chamarPrivado('processarFimDaPergunta', 'ABCD');

        $eventos = $this->eventosLogados('info');
        $this->assertContains('jogador_entrou', $eventos);
        $this->assertContains('rodada_iniciada', $eventos);
        $this->assertContains('voto_registrado', $eventos);
        $this->assertContains('rodada_apurada', $eventos);

        $entrou = collect($this->logsMultiplayer('info'))->firstWhere('mensagem', 'jogador_entrou');
        $this->assertSame('ABCD', $entrou['contexto']['pin']);
        $this->assertSame($u1->id, $entrou['contexto']['user_id']);
    }

    public function test_acoes_recusadas_sao_avisadas(): void
    {
        $c = $this->conexao(9);
        $this->enviar($c, ['action' => 'join', 'pin' => 'ABCD', 'token' => 'invalido']);
        $this->enviar($c, ['action' => 'votar', 'pin' => 'ABCD', 'alternativa_id' => 0]);

        $avisos = $this->eventosLogados('warning');
        $this->assertContains('join_recusado', $avisos);
        $this->assertContains('acao_nao_autenticada', $avisos);
    }

    public function test_token_nunca_aparece_nos_logs(): void
    {
        [$partida, $u1] = $this->conectado();
        $token = \App\Support\WebSocketToken::emitir($u1->id, 'ABCD');

        $this->assertStringNotContainsString($token, json_encode($this->logsMultiplayer()));
    }
}
