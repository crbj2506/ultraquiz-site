<?php

namespace App\Services\Lobby;

use App\Models\Partida;
use App\Models\PartidaMultiplayer;

class InicioPartidaService
{
    /** @throws LobbyException quando o início não é permitido */
    public function validar(PartidaMultiplayer $partida, ?int $userId): void
    {
        if ($userId !== $partida->user_id) {
            throw new LobbyException('Apenas o anfitrião pode iniciar a partida.');
        }

        // Só uma sala em espera pode ser iniciada (evita reiniciar partida em andamento ou encerrada)
        if ($partida->status !== 'waiting') {
            throw new LobbyException('Esta partida não pode ser iniciada.');
        }

        // Pelo menos 1 jogador em CADA equipe principal
        $equipeA = $partida->equipes->where('nome', 'Equipe A')->first();
        $equipeB = $partida->equipes->where('nome', 'Equipe B')->first();

        $countA = $equipeA ? $equipeA->jogadores()->count() : 0;
        $countB = $equipeB ? $equipeB->jogadores()->count() : 0;

        if ($countA < 1 || $countB < 1) {
            throw new LobbyException('Ambas as equipes precisam de pelo menos 1 jogador para começar.');
        }
    }

    public function iniciar(PartidaMultiplayer $partida): void
    {
        foreach ($partida->equipes as $equipe) {
            $equipe->update(['pontuacao' => 0]);
        }

        // Gera as questões usando a lógica existente do modo solo
        $p = new Partida();
        $p->criar(PartidaMultiplayer::quantidadeQuestoes());
        $questoes = $p->getState()['questoes_data'];

        $partida->update([
            'status' => 'playing',
            'questoes_json' => json_encode($questoes),
            'pergunta_atual_index' => 0,
        ]);
    }
}
