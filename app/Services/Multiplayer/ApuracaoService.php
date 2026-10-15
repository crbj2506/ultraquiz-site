<?php

namespace App\Services\Multiplayer;

use App\Models\EquipeMultiplayer;
use App\Models\Estatistica;
use App\Models\PartidaMultiplayer;
use App\Models\Questao;

class ApuracaoService
{
    public const ID_RESPOSTA_CORRETA = 0;
    public const PONTOS_POR_ACERTO = 10;

    /**
     * Apura a rodada: pontua equipes, dá XP, grava estatísticas individuais.
     *
     * @param array $votosDetalhados [equipe_id => [user_id => ['alt', 'time']]]
     * @return array{questao: Questao, equipes: array, scores: array}
     */
    public function apurar(PartidaMultiplayer $pm, array $questaoData, array $equipesNaSala, array $votosDetalhados): array
    {
        $questao = Questao::with('respostas')->find($questaoData['id']);
        $resultado = [];
        $estatisticas = [];

        foreach ($equipesNaSala as $equipeId) {
            $votosEquipe = $votosDetalhados[$equipeId] ?? [];
            $votoId = ApuradorVotos::votoDaEquipe($votosEquipe);
            $acertou = $votoId !== null && $votoId == self::ID_RESPOSTA_CORRETA;

            $resultado[$equipeId] = [
                'voto' => $this->textoDoVoto($questao, $votoId),
                'acertou' => $acertou,
                'votou' => $votoId !== null,
            ];

            // Cada voto individual entra no histórico, que define a dificuldade da questão.
            foreach ($votosEquipe as $voto) {
                $alternativaId = (int) ($voto['alt'] ?? -1);
                if ($alternativaId >= 0) {
                    $estatisticas[] = [
                        'questao_id' => $questao->id,
                        'resposta_id' => $alternativaId === 0 ? null : $alternativaId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            $equipe = EquipeMultiplayer::with('jogadores.user')->find($equipeId);

            if ($votoId !== null) {
                $acertou ? $questao->increment('acertos') : $questao->increment('erros');
                $this->darExperiencia($equipe, $questao, $acertou);
            }

            if ($acertou && $equipe) {
                $equipe->increment('pontuacao', self::PONTOS_POR_ACERTO);
            }
        }

        // Gravado só depois do XP, para que todas as equipes sejam avaliadas sobre a mesma base.
        if ($estatisticas) {
            Estatistica::insert($estatisticas);
        }

        return [
            'questao' => $questao,
            'equipes' => $resultado,
            'scores' => $this->placar($pm),
        ];
    }

    public function placar(PartidaMultiplayer $pm): array
    {
        return EquipeMultiplayer::where('partida_multiplayer_id', $pm->id)->pluck('pontuacao', 'id')->toArray();
    }

    private function textoDoVoto(Questao $questao, $votoId): string
    {
        if ($votoId === null) {
            return 'Não votou';
        }
        if ($votoId == self::ID_RESPOSTA_CORRETA) {
            return $questao->resposta;
        }

        $resposta = $questao->respostas->find($votoId);

        return $resposta ? $resposta->alternativa : 'Erro';
    }

    private function darExperiencia(?EquipeMultiplayer $equipe, Questao $questao, bool $acertou): void
    {
        if (!$equipe) {
            return;
        }

        foreach ($equipe->jogadores as $jogador) {
            if ($jogador->user && empty($jogador->user->is_guest)) {
                $jogador->user->adicionarExperienciaBaseadoNaQuestao($questao, $acertou);
            }
        }
    }
}
