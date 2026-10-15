<?php

namespace App\Services\Lobby;

use App\Models\EquipeMultiplayer;
use App\Models\JogadorPartida;
use App\Models\PartidaMultiplayer;

class EscolhaEquipeService
{
    /**
     * Coloca o usuário na equipe escolhida, balanceando se a diferença for de 2 ou mais jogadores.
     *
     * @return array{0: EquipeMultiplayer, 1: ?string} equipe final e mensagem de balanceamento
     */
    public function escolher(PartidaMultiplayer $partida, EquipeMultiplayer $equipe, int $userId): array
    {
        $equipeFinal = $equipe;
        $mensagem = null;

        $outraEquipe = $partida->equipes->where('id', '!=', $equipe->id)->first();
        if ($outraEquipe) {
            $qtdEscolhida = $equipe->jogadores->where('user_id', '!=', $userId)->count();
            $qtdOutra = $outraEquipe->jogadores->where('user_id', '!=', $userId)->count();

            if ($qtdEscolhida - $qtdOutra >= 2) {
                $equipeFinal = $outraEquipe;
                $mensagem = 'Para manter o jogo justo, você foi alocado na ' . $outraEquipe->nome . '!';
            }
        }

        // Remove o jogador de outras equipes desta mesma partida (se houver)
        JogadorPartida::where('partida_multiplayer_id', $partida->id)
            ->where('user_id', $userId)
            ->get()
            ->each->delete();

        JogadorPartida::create([
            'partida_multiplayer_id' => $partida->id,
            'equipe_multiplayer_id' => $equipeFinal->id,
            'user_id' => $userId,
        ]);

        return [$equipeFinal, $mensagem];
    }
}
