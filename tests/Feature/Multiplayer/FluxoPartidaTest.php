<?php

namespace Tests\Feature\Multiplayer;

use App\Models\PartidaMultiplayer;

class FluxoPartidaTest extends ServidorWebSocketTestCase
{
    /** Cria sala em andamento com um jogador em cada equipe, ambos conectados. */
    private function salaComDoisJogadoresConectados(int $questoes = 1): array
    {
        $partida = $this->salaEmAndamento($questoes);
        $a = $this->equipe($partida, 'Equipe A');
        $b = $this->equipe($partida, 'Equipe B');
        $u1 = $this->colocarNaEquipe($partida, $a);
        $u2 = $this->colocarNaEquipe($partida, $b);
        $c1 = $this->conexao(1);
        $c2 = $this->conexao(2);

        $this->entrar($c1, $u1, 'abcd');
        $this->entrar($c2, $u2, 'abcd');

        return [$partida, $a, $b, $u1, $u2, $c1, $c2];
    }

    public function test_primeiro_join_em_partida_playing_dispara_a_primeira_pergunta(): void
    {
        $partida = $this->salaEmAndamento();
        $u1 = $this->colocarNaEquipe($partida, $this->equipe($partida, 'Equipe A'));
        $c1 = $this->conexao(1);

        $this->entrar($c1, $u1, 'abcd');

        $pergunta = $c1->ultima('pergunta');
        $this->assertNotNull($pergunta);
        $this->assertSame(1, $pergunta['index']);
        $this->assertSame(1, $pergunta['total']);
        $this->assertSame(60, $pergunta['tempo']);
        $this->assertCount(5, $pergunta['alternativas']);
        $this->assertContains(0, array_column($pergunta['alternativas'], 'id'));
    }

    public function test_pin_e_normalizado_para_maiusculas_e_jogador_e_ligado_a_sua_equipe(): void
    {
        [, $a, , $u1] = $this->salaComDoisJogadoresConectados();

        $sala = $this->lerPropriedade('salas')['ABCD'];
        $this->assertSame($a->id, $sala['player_equipe'][$u1->id]);
    }

    public function test_segundo_jogador_recebe_a_pergunta_atual_sem_reiniciar_a_rodada(): void
    {
        [, , , , , $c1, $c2] = $this->salaComDoisJogadoresConectados();

        $this->assertCount(1, array_filter($c1->recebidas, fn ($m) => $m['type'] === 'pergunta'));
        $this->assertNotNull($c2->ultima('pergunta'));
        $this->assertContains('system', $c1->tipos(), 'Os demais são avisados da entrada.');
    }

    public function test_voto_e_retransmitido_e_nao_encerra_a_rodada_enquanto_faltar_jogador(): void
    {
        [, , , $u1, , $c1, $c2] = $this->salaComDoisJogadoresConectados();

        $this->enviar($c1, ['action' => 'votar', 'pin' => 'ABCD', 'user_id' => $u1->id, 'alternativa_id' => 0]);

        $this->assertSame($u1->id, $c2->ultima('voto')['user_id']);
        $this->assertNull($c2->ultima('fim_pergunta'));
    }

    public function test_quando_todos_conectados_votam_a_rodada_termina_antes_do_timer(): void
    {
        [$partida, $a, $b, $u1, $u2, $c1, $c2] = $this->salaComDoisJogadoresConectados();

        $this->enviar($c1, ['action' => 'votar', 'pin' => 'ABCD', 'user_id' => $u1->id, 'alternativa_id' => 0]);
        $this->enviar($c2, ['action' => 'votar', 'pin' => 'ABCD', 'user_id' => $u2->id, 'alternativa_id' => json_decode($partida->questoes_json, true)[0]['opcoes'][1]]);

        $fim = $c1->ultima('fim_pergunta');
        $this->assertSame('Todos votaram!', $fim['message']);
        $this->assertSame(10, $fim['scores'][$a->id]);
        $this->assertSame(0, $fim['scores'][$b->id]);
    }

    public function test_voto_em_alternativa_inexistente_ou_invalida_e_ignorado_e_a_rodada_continua(): void
    {
        [, , , $u1, $u2, $c1, $c2] = $this->salaComDoisJogadoresConectados();

        foreach ([12345, 'abc', -1, null] as $invalida) {
            $this->enviar($c1, ['action' => 'votar', 'pin' => 'ABCD', 'user_id' => $u1->id, 'alternativa_id' => $invalida]);
        }

        $this->assertNull($c2->ultima('voto'));
        $this->assertNull($c2->ultima('fim_pergunta'));

        // Um voto válido logo depois ainda é aceito.
        $this->enviar($c1, ['action' => 'votar', 'pin' => 'ABCD', 'user_id' => $u1->id, 'alternativa_id' => '0']);
        $this->assertNotNull($c2->ultima('voto'));
    }

    public function test_voto_de_quem_nao_pertence_a_uma_equipe_e_ignorado(): void
    {
        [$partida, , , , , $c1] = $this->salaComDoisJogadoresConectados();
        $espectador = $this->criarUsuario();
        $c3 = $this->conexao(3);
        $this->entrar($c3, $espectador, 'ABCD');

        $this->enviar($c3, ['action' => 'votar', 'pin' => 'ABCD', 'alternativa_id' => 0]);

        $this->assertNull($c1->ultima('voto'));
    }

    public function test_start_do_anfitriao_marca_sala_em_andamento_e_manda_todos_para_a_tela_de_jogo(): void
    {
        [$partida, , , , , $c1, $c2] = $this->salaComDoisJogadoresConectados();
        $cHost = $this->conexao(9);
        $this->entrar($cHost, \App\Models\User::find($partida->user_id), 'ABCD');

        $this->enviar($cHost, ['action' => 'start', 'pin' => 'ABCD']);

        $this->assertNotNull($c1->ultima('redirect'));
        $this->assertNotNull($c2->ultima('redirect'));
    }

    public function test_empate_na_ultima_pergunta_ativa_morte_subita(): void
    {
        [$partida, , , , , $c1] = $this->salaComDoisJogadoresConectados();

        // 0 x 0 na única pergunta: ninguém votou, então empata.
        $this->chamarPrivado('processarFimDaPergunta', 'ABCD');
        $this->loop->dispararTimerUnico();

        $questoes = json_decode($partida->fresh()->questoes_json, true);
        $this->assertCount(2, $questoes);
        $this->assertSame('sudden_death', $questoes[1]['modo']);
        $this->assertStringContainsString('Morte Súbita', $c1->ultima('system')['message']);
        $this->assertSame(2, $c1->ultima('pergunta')['index']);
        $this->assertSame('playing', $partida->fresh()->status);
    }

    public function test_morte_subita_para_apos_3_rodadas_e_a_partida_termina_empatada(): void
    {
        [$partida, $a, $b, , , $c1] = $this->salaComDoisJogadoresConectados();

        // Simula 3 mortes súbitas já ocorridas, e estamos na última delas.
        $questoes = json_decode($partida->questoes_json, true);
        for ($i = 0; $i < 3; $i++) {
            $questoes[] = array_merge($questoes[0], ['modo' => 'sudden_death']);
        }
        $partida->update(['questoes_json' => json_encode($questoes), 'pergunta_atual_index' => 3]);

        $this->chamarPrivado('processarFimDaPergunta', 'ABCD');
        $this->loop->dispararTimerUnico();

        $fim = $c1->ultima('finalizada');
        $this->assertNotNull($fim);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $fim['winners']);
        $this->assertStringContainsString('Limite de desempates', $c1->ultima('system')['message']);
        $this->assertCount(4, json_decode($partida->fresh()->questoes_json, true));
        $this->assertSame('finished', $partida->fresh()->status);
    }

    public function test_fim_de_jogo_informa_a_equipe_vencedora_e_encerra_a_partida(): void
    {
        [$partida, $a, $b, $u1, $u2, $c1, $c2] = $this->salaComDoisJogadoresConectados();

        $this->enviar($c1, ['action' => 'votar', 'pin' => 'ABCD', 'user_id' => $u1->id, 'alternativa_id' => 0]);
        $this->enviar($c2, ['action' => 'votar', 'pin' => 'ABCD', 'user_id' => $u2->id, 'alternativa_id' => json_decode($partida->questoes_json, true)[0]['opcoes'][1]]);
        $this->loop->dispararTimerUnico();

        $fim = $c2->ultima('finalizada');
        $this->assertSame([$a->id], $fim['winners']);
        $this->assertSame(10, $fim['scores'][$a->id]);
        $this->assertSame('finished', PartidaMultiplayer::find($partida->id)->status);
        $this->assertNotEmpty($fim['resultados_finais']);
    }

    public function test_revanche_so_reinicia_quando_as_duas_equipes_votam(): void
    {
        [$partida, $a, $b, $u1, $u2, $c1, $c2] = $this->salaComDoisJogadoresConectados();
        $a->update(['pontuacao' => 10]);

        $this->enviar($c1, ['action' => 'rematch_vote', 'pin' => 'ABCD', 'user_id' => $u1->id]);
        $status = $c2->ultima('rematch_status');
        $this->assertSame([$a->id], $status['equipes_que_votaram']);
        $this->assertSame([$b->id], $status['aguardando']);
        $this->assertNull($c2->ultima('reset'));

        $this->enviar($c2, ['action' => 'rematch_vote', 'pin' => 'ABCD', 'user_id' => $u2->id]);

        $this->assertNotNull($c1->ultima('reset'));
        $this->assertSame(0, (int) $a->fresh()->pontuacao);
    }
}
