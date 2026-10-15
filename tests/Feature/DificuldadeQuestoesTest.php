<?php

namespace Tests\Feature;

use App\Models\Estatistica;
use App\Models\Partida;
use App\Models\Questao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DificuldadeQuestoesTest extends TestCase
{
    use RefreshDatabase;

    /** Registra respostas: $acertos corretas (resposta_id nulo) e $erros erradas. */
    private function responder(Questao $questao, int $acertos, int $erros): void
    {
        for ($i = 0; $i < $acertos; $i++) {
            Estatistica::create(['questao_id' => $questao->id, 'resposta_id' => null]);
        }
        $errada = $questao->respostas()->first()->id;
        for ($i = 0; $i < $erros; $i++) {
            Estatistica::create(['questao_id' => $questao->id, 'resposta_id' => $errada]);
        }
    }

    /** @return array{facil: int[], media: int[], dificil: int[], semDados: int[]} ids por faixa */
    private function banco(int $facil, int $media, int $dificil, int $semDados): array
    {
        $this->criarQuestoesAprovadas($facil + $media + $dificil + $semDados + 1);
        $questoes = Questao::orderBy('id')->get()->values();
        $faixas = ['facil' => [], 'media' => [], 'dificil' => [], 'semDados' => []];
        $i = 0;

        foreach ([['facil', $facil, 9, 1], ['media', $media, 5, 5], ['dificil', $dificil, 1, 9], ['semDados', $semDados, 1, 0]] as [$nome, $qtd, $acertos, $erros]) {
            for ($n = 0; $n < $qtd; $n++, $i++) {
                $this->responder($questoes[$i], $acertos, $erros);
                $faixas[$nome][] = $questoes[$i]->id;
            }
        }

        // Questão-isca: as respostas mais recentes são dela, e a regra "respondida recentemente"
        // a exclui sozinha, sem tirar as demais da seleção.
        $this->responder($questoes[$i], 200, 0);

        return $faixas;
    }

    public function test_distribuicao_e_25_50_25(): void
    {
        $this->assertSame(['facil' => 5, 'media' => 10, 'dificil' => 5], Partida::distribuicaoPorDificuldade(20));
        $this->assertSame(['facil' => 3, 'media' => 4, 'dificil' => 3], Partida::distribuicaoPorDificuldade(10));
        $this->assertSame(['facil' => 1, 'media' => 3, 'dificil' => 1], Partida::distribuicaoPorDificuldade(5));
        $this->assertSame(['facil' => 0, 'media' => 1, 'dificil' => 0], Partida::distribuicaoPorDificuldade(1));
    }

    public function test_scopes_classificam_pela_taxa_de_acerto(): void
    {
        $f = $this->banco(2, 2, 2, 1);

        $this->assertEqualsCanonicalizing($f['facil'], Questao::facil()->pluck('id')->all());
        $this->assertEqualsCanonicalizing($f['dificil'], Questao::dificil()->pluck('id')->all());
        // Sem respostas suficientes (1 resposta) conta como média
        $this->assertEqualsCanonicalizing(array_merge($f['media'], $f['semDados']), Questao::media()->pluck('id')->all());
    }

    public function test_questao_sem_nenhuma_resposta_e_media(): void
    {
        $this->criarQuestoesAprovadas(1);

        $this->assertCount(1, Questao::media()->get());
        $this->assertCount(0, Questao::facil()->get());
        $this->assertCount(0, Questao::dificil()->get());
    }

    public function test_partida_de_20_tem_5_faceis_10_medias_e_5_dificeis_em_ordem_crescente(): void
    {
        config(['app.numero_questoes_partida' => 20]);
        $f = $this->banco(10, 20, 10, 5);

        $p = new Partida();
        $p->criar();
        $ids = $p->questoes->pluck('id')->all();

        $this->assertCount(20, $ids);
        $this->assertSame($ids, array_values(array_unique($ids)), 'sem repetição');

        $faixaDe = fn ($id) => in_array($id, $f['facil']) ? 'facil' : (in_array($id, $f['dificil']) ? 'dificil' : 'media');
        $faixas = array_map($faixaDe, $ids);

        $this->assertSame(
            array_merge(array_fill(0, 5, 'facil'), array_fill(0, 10, 'media'), array_fill(0, 5, 'dificil')),
            $faixas
        );
    }

    public function test_faixa_sem_questoes_suficientes_e_completada_com_aleatorias(): void
    {
        config(['app.numero_questoes_partida' => 20]);
        $this->banco(0, 30, 0, 0);

        $p = new Partida();
        $p->criar();

        $this->assertCount(20, $p->questoes);
        $this->assertCount(20, $p->questoes->pluck('id')->unique());
    }

    public function test_quantidade_explicita_tem_prioridade_e_respeita_a_proporcao(): void
    {
        config(['app.numero_questoes_partida' => 20]);
        $f = $this->banco(10, 20, 10, 5);

        $p = new Partida();
        $p->criar(10);
        $ids = $p->questoes->pluck('id')->all();

        $this->assertCount(10, $ids);
        $this->assertCount(3, array_intersect($ids, $f['facil']));
        $this->assertCount(3, array_intersect($ids, $f['dificil']));
    }
}
