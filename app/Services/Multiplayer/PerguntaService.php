<?php

namespace App\Services\Multiplayer;

use App\Models\PartidaMultiplayer;
use App\Models\Questao;

class PerguntaService
{
    public const TEMPO_RODADA = 60;
    public const INTERVALO_ENTRE_PERGUNTAS = 8;
    public const MAX_MORTE_SUBITA = 3;

    public function questoes(PartidaMultiplayer $pm): array
    {
        return $pm->questoes_json ? (json_decode($pm->questoes_json, true) ?: []) : [];
    }

    /** Dados da pergunta no formato enviado aos clientes. */
    public function dados(PartidaMultiplayer $pm, array $questoes, int $index): array
    {
        if (!isset($questoes[$index])) {
            throw new \Exception("Dados da questão no índice $index não encontrados.");
        }

        $qData = $questoes[$index];
        $questao = Questao::with('respostas')->find($qData['id']);
        if (!$questao) {
            throw new \Exception("Questão ID {$qData['id']} não encontrada no banco de dados.");
        }

        $alternativas = [];
        foreach ($qData['opcoes'] as $respostaId) {
            if ($respostaId === 0) {
                $alternativas[] = ['id' => 0, 'texto' => $questao->resposta];
            } elseif ($resposta = $questao->respostas->find($respostaId)) {
                $alternativas[] = ['id' => $resposta->id, 'texto' => $resposta->alternativa];
            }
        }

        return [
            'type' => 'pergunta',
            'pergunta' => $questao->pergunta,
            'alternativas' => $alternativas,
            'tempo' => self::TEMPO_RODADA,
            'index' => $index + 1,
            'total' => count($questoes),
        ];
    }

    /** A alternativa votada precisa estar entre as opções da pergunta atual. */
    public function alternativaValida(?PartidaMultiplayer $pm, $alternativaId): bool
    {
        if (!is_int($alternativaId) && !(is_string($alternativaId) && ctype_digit($alternativaId))) {
            return false;
        }

        $questoes = $pm ? $this->questoes($pm) : [];
        $opcoes = $questoes[$pm?->pergunta_atual_index]['opcoes'] ?? null;

        return is_array($opcoes) && in_array((int) $alternativaId, $opcoes, true);
    }

    public function rodadasDeMorteSubita(array $questoes): int
    {
        return collect($questoes)->where('modo', 'sudden_death')->count();
    }

    /** Sorteia uma questão ainda não usada para o desempate; null se não houver nenhuma. */
    public function novaQuestaoDeMorteSubita(array $questoes): ?array
    {
        $questao = Questao::with('respostas')
            ->whereNotIn('id', collect($questoes)->pluck('id')->all())
            ->has('respostas')
            ->inRandomOrder()
            ->first();

        if (!$questao) {
            return null;
        }

        // Até 4 respostas erradas mais a correta (id 0)
        $opcoes = $questao->respostas->shuffle()->take(4)->pluck('id')->toArray();
        $opcoes[] = 0;
        shuffle($opcoes);

        return [
            'id' => $questao->id,
            'respAnt' => null,
            'opcoes' => $opcoes,
            'modo' => 'sudden_death',
        ];
    }
}
