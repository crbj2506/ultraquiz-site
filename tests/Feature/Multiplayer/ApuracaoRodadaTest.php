<?php

namespace Tests\Feature\Multiplayer;

use App\Models\Estatistica;
use App\Models\Questao;
use App\Models\User;

class ApuracaoRodadaTest extends ServidorWebSocketTestCase
{
    /** Converte [equipe_id => [user_id => [alt, tempo]]] no formato interno do servidor. */
    private function votos(array $porEquipe): array
    {
        $out = [];
        foreach ($porEquipe as $eid => $votos) {
            foreach ($votos as $uid => [$alt, $time]) {
                $out[$eid][$uid] = ['alt' => $alt, 'time' => $time];
            }
        }
        return $out;
    }

    private function alternativaErrada($partida): int
    {
        return json_decode($partida->questoes_json, true)[0]['opcoes'][1];
    }

    public function test_maioria_da_equipe_define_o_voto_e_acerto_vale_10_pontos(): void
    {
        $partida = $this->salaEmAndamento();
        $a = $this->equipe($partida, 'Equipe A');
        $b = $this->equipe($partida, 'Equipe B');
        $u1 = $this->colocarNaEquipe($partida, $a);
        $u2 = $this->colocarNaEquipe($partida, $a);
        $u3 = $this->colocarNaEquipe($partida, $a);
        $u4 = $this->colocarNaEquipe($partida, $b);
        $errada = $this->alternativaErrada($partida);

        $this->prepararEstadoSala('ABCD',
            [$u1->id => $a->id, $u2->id => $a->id, $u3->id => $a->id, $u4->id => $b->id],
            $this->votos([
                $a->id => [$u1->id => [0, 1.0], $u2->id => [0, 2.0], $u3->id => [$errada, 0.5]],
                $b->id => [$u4->id => [$errada, 1.0]],
            ])
        );

        $this->chamarPrivado('processarFimDaPergunta', 'ABCD');

        $this->assertSame(10, (int) $a->fresh()->pontuacao);
        $this->assertSame(0, (int) $b->fresh()->pontuacao);

        $sala = $this->lerPropriedade('salas')['ABCD'];
        $this->assertTrue($sala['resultados'][0]['equipes'][$a->id]['acertou']);
        $this->assertFalse($sala['resultados'][0]['equipes'][$b->id]['acertou']);
        $this->assertSame('waiting_next', $sala['status']);
    }

    public function test_empate_interno_e_decidido_pelo_voto_mais_rapido(): void
    {
        $partida = $this->salaEmAndamento();
        $a = $this->equipe($partida, 'Equipe A');
        $u1 = $this->colocarNaEquipe($partida, $a);
        $u2 = $this->colocarNaEquipe($partida, $a);
        $errada = $this->alternativaErrada($partida);

        // 1 voto certo (mais lento) x 1 voto errado (mais rápido): o rápido vence.
        $this->prepararEstadoSala('ABCD', [$u1->id => $a->id, $u2->id => $a->id],
            $this->votos([$a->id => [$u1->id => [0, 5.0], $u2->id => [$errada, 1.0]]]));

        $this->chamarPrivado('processarFimDaPergunta', 'ABCD');

        $this->assertFalse($this->lerPropriedade('salas')['ABCD']['resultados'][0]['equipes'][$a->id]['acertou']);
        $this->assertSame(0, (int) $a->fresh()->pontuacao);
    }

    public function test_equipe_que_nao_votou_nao_pontua_e_nao_afeta_estatisticas(): void
    {
        $partida = $this->salaEmAndamento();
        $a = $this->equipe($partida, 'Equipe A');
        $u1 = $this->colocarNaEquipe($partida, $a);

        $this->prepararEstadoSala('ABCD', [$u1->id => $a->id], []);

        $this->chamarPrivado('processarFimDaPergunta', 'ABCD', 'Tempo esgotado!');

        $res = $this->lerPropriedade('salas')['ABCD']['resultados'][0]['equipes'][$a->id];
        $this->assertSame('Não votou', $res['voto']);
        $this->assertFalse($res['votou']);
        $this->assertSame(0, (int) $a->fresh()->pontuacao);
        $this->assertSame(0, Estatistica::count());
        $questao = Questao::firstOrFail();
        $this->assertSame(0, (int) $questao->acertos + (int) $questao->erros);
    }

    public function test_grava_uma_estatistica_por_voto_individual_e_acerto_tem_resposta_nula(): void
    {
        $partida = $this->salaEmAndamento();
        $a = $this->equipe($partida, 'Equipe A');
        $u1 = $this->colocarNaEquipe($partida, $a);
        $u2 = $this->colocarNaEquipe($partida, $a);
        $u3 = $this->colocarNaEquipe($partida, $a);
        $errada = $this->alternativaErrada($partida);

        $this->prepararEstadoSala('ABCD', [$u1->id => $a->id, $u2->id => $a->id, $u3->id => $a->id],
            $this->votos([$a->id => [$u1->id => [0, 1.0], $u2->id => [0, 2.0], $u3->id => [$errada, 3.0]]]));

        $this->chamarPrivado('processarFimDaPergunta', 'ABCD');

        $this->assertSame(3, Estatistica::count());
        $this->assertSame(2, Estatistica::whereNull('resposta_id')->count());
        $this->assertSame(1, Estatistica::where('resposta_id', $errada)->count());
    }

    public function test_acertos_e_erros_da_questao_contam_por_equipe_e_nao_por_voto(): void
    {
        $partida = $this->salaEmAndamento();
        $a = $this->equipe($partida, 'Equipe A');
        $b = $this->equipe($partida, 'Equipe B');
        $u1 = $this->colocarNaEquipe($partida, $a);
        $u2 = $this->colocarNaEquipe($partida, $a);
        $u3 = $this->colocarNaEquipe($partida, $b);
        $errada = $this->alternativaErrada($partida);

        $this->prepararEstadoSala('ABCD', [$u1->id => $a->id, $u2->id => $a->id, $u3->id => $b->id],
            $this->votos([
                $a->id => [$u1->id => [0, 1.0], $u2->id => [0, 2.0]],
                $b->id => [$u3->id => [$errada, 1.0]],
            ]));

        $this->chamarPrivado('processarFimDaPergunta', 'ABCD');

        $questao = Questao::firstOrFail();
        $this->assertSame(1, (int) $questao->acertos);
        $this->assertSame(1, (int) $questao->erros);
    }

    public function test_apenas_jogadores_nao_convidados_recebem_experiencia(): void
    {
        $partida = $this->salaEmAndamento();
        $a = $this->equipe($partida, 'Equipe A');
        $registrado = $this->colocarNaEquipe($partida, $a);
        $convidado = $this->colocarNaEquipe($partida, $a, $this->criarUsuario(['is_guest' => true]));

        $this->prepararEstadoSala('ABCD', [$registrado->id => $a->id, $convidado->id => $a->id],
            $this->votos([$a->id => [$registrado->id => [0, 1.0], $convidado->id => [0, 2.0]]]));

        $this->chamarPrivado('processarFimDaPergunta', 'ABCD');

        // Questão com menos de 10 respostas: acerto vale +2 XP.
        $this->assertSame(2, (int) User::find($registrado->id)->experiencia);
        $this->assertSame(0, (int) User::find($convidado->id)->experiencia);
    }

    public function test_xp_usa_a_dificuldade_do_historico_individual_de_estatisticas(): void
    {
        $partida = $this->salaEmAndamento();
        $a = $this->equipe($partida, 'Equipe A');
        $jogador = $this->colocarNaEquipe($partida, $a);
        $questaoId = json_decode($partida->questoes_json, true)[0]['id'];
        $errada = Questao::find($questaoId)->respostas->first()->id;

        // 12 respostas individuais erradas: questão difícil (taxa 0%), mesmo com acertos/erros zerados.
        for ($i = 0; $i < 12; $i++) {
            Estatistica::create(['questao_id' => $questaoId, 'resposta_id' => $errada]);
        }

        $this->prepararEstadoSala('ABCD', [$jogador->id => $a->id],
            $this->votos([$a->id => [$jogador->id => [0, 1.0]]]));

        $this->chamarPrivado('processarFimDaPergunta', 'ABCD');

        $this->assertSame(5, (int) User::find($jogador->id)->experiencia);
    }

    public function test_fim_da_pergunta_envia_placar_e_resposta_correta_aos_conectados(): void
    {
        $partida = $this->salaEmAndamento();
        $a = $this->equipe($partida, 'Equipe A');
        $u1 = $this->colocarNaEquipe($partida, $a);
        $conn = $this->conexao(1);

        $this->prepararEstadoSala('ABCD', [$u1->id => $a->id], $this->votos([$a->id => [$u1->id => [0, 1.0]]]));
        $this->lerPropriedade('salas')['ABCD']['conns']->attach($conn, $u1->id);

        $this->chamarPrivado('processarFimDaPergunta', 'ABCD', 'Todos votaram!');

        $msg = $conn->ultima('fim_pergunta');
        $this->assertSame('Todos votaram!', $msg['message']);
        $this->assertSame('Resposta correta 1', $msg['correta']);
        $this->assertSame(0, $msg['id_correto']);
        $this->assertSame(10, $msg['scores'][$a->id]);
    }
}
