<?php

namespace Tests\Feature\Multiplayer;

class ReconexaoESincronizacaoTest extends ServidorWebSocketTestCase
{
    private function partidaComDuasEquipes(): array
    {
        $partida = $this->salaEmAndamento(2);
        $a = $this->criarUsuario();
        $b = $this->criarUsuario();
        $this->colocarNaEquipe($partida, $this->equipe($partida, 'Equipe A'), $a);
        $this->colocarNaEquipe($partida, $this->equipe($partida, 'Equipe B'), $b);

        return [$partida, $a, $b];
    }

    public function test_reconexao_no_meio_da_rodada_recebe_pergunta_com_o_tempo_restante(): void
    {
        [, $a, $b] = $this->partidaComDuasEquipes();

        $c1 = $this->conexao(1);
        $this->entrar($c1, $a);

        $this->salasSet('tempo_restante', 37);

        $c2 = $this->conexao(2);
        $this->entrar($c2, $b);

        $pergunta = $c2->ultima('pergunta');
        $this->assertNotNull($pergunta);
        $this->assertSame(37, $pergunta['tempo']);
        $this->assertSame(1, count($this->loop->timers), 'entrar não cria um segundo timer');
    }

    public function test_jogador_que_reconecta_apos_fechar_a_aba_volta_a_receber_eventos(): void
    {
        [, $a, $b] = $this->partidaComDuasEquipes();

        $c1 = $this->conexao(1);
        $c2 = $this->conexao(2);
        $this->entrar($c1, $a);
        $this->entrar($c2, $b);

        $this->servidor->onClose($c2);

        $c2nova = $this->conexao(3);
        $this->entrar($c2nova, $b);

        $this->assertNotNull($c2nova->ultima('pergunta'));
        $antes = count($c2nova->recebidas);
        $this->enviar($c1, ['action' => 'votar', 'pin' => 'ABCD', 'alternativa_id' => 0]);
        $this->assertGreaterThan($antes, count($c2nova->recebidas));
        $this->assertNotNull($c2nova->ultima('voto'));
    }

    public function test_entrar_durante_o_intervalo_nao_reinicia_a_rodada(): void
    {
        [, $a, $b] = $this->partidaComDuasEquipes();

        $c1 = $this->conexao(1);
        $this->entrar($c1, $a);
        $this->enviar($c1, ['action' => 'votar', 'pin' => 'ABCD', 'alternativa_id' => 0]);
        $this->salasSet('status', 'waiting_next');
        $timersAntes = count($this->loop->timers);

        $c2 = $this->conexao(2);
        $this->entrar($c2, $b);

        $this->assertNull($c2->ultima('pergunta'));
        $this->assertSame($timersAntes, count($this->loop->timers));
    }

    public function test_reentrada_em_partida_encerrada_nao_dispara_rodada(): void
    {
        $partida = $this->salaEmAndamento(1);
        $a = $this->criarUsuario();
        $this->colocarNaEquipe($partida, $this->equipe($partida, 'Equipe A'), $a);
        $partida->update(['status' => 'finished']);

        $c1 = $this->conexao(1);
        $this->entrar($c1, $a);

        $this->assertNull($c1->ultima('pergunta'));
        $this->assertSame([], $this->loop->timers);
    }

    public function test_jogador_sem_equipe_entra_mas_o_voto_e_ignorado(): void
    {
        $this->partidaComDuasEquipes();
        $espectador = $this->criarUsuario();

        $c1 = $this->conexao(1);
        $this->entrar($c1, $espectador);
        $this->enviar($c1, ['action' => 'votar', 'pin' => 'ABCD', 'alternativa_id' => 0]);

        $this->assertNull($c1->ultima('voto'));
        $this->assertNotContains('voto_registrado', $this->eventosLogados('info'));
    }

    public function test_voto_em_sala_inexistente_na_memoria_e_ignorado_sem_erro(): void
    {
        $c1 = $this->conexao(1);
        $this->enviar($c1, ['action' => 'votar', 'pin' => 'ZZZZ', 'alternativa_id' => 0]);

        $this->assertSame([], $c1->recebidas);
        $this->assertContains('acao_nao_autenticada', $this->eventosLogados('warning'));
    }

    public function test_join_com_token_de_outra_sala_e_recusado(): void
    {
        $this->salaEmAndamento(1);
        $a = $this->criarUsuario();
        $c1 = $this->conexao(1);

        $this->enviar($c1, [
            'action' => 'join',
            'pin' => 'ABCD',
            'token' => \App\Support\WebSocketToken::emitir($a->id, 'WXYZ'),
        ]);

        $this->assertSame(['auth_error'], $c1->tipos());
        $this->assertSame([], $this->loop->timers);
    }

    private function salasSet(string $chave, $valor, string $pin = 'ABCD'): void
    {
        $salas = $this->lerPropriedade('salas');
        $salas[$pin][$chave] = $valor;
        $this->definirPropriedade('salas', $salas);
    }
}
