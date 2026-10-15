<?php

namespace App\Services\Lobby;

use App\Models\EquipeMultiplayer;
use App\Models\PartidaMultiplayer;
use Illuminate\Support\Str;

class CriacaoSalaService
{
    public function criar(int $anfitriaoId): PartidaMultiplayer
    {
        $partida = PartidaMultiplayer::create([
            'pin' => $this->gerarPinUnico(),
            'status' => 'waiting',
            'user_id' => $anfitriaoId,
        ]);

        EquipeMultiplayer::create(['partida_multiplayer_id' => $partida->id, 'nome' => 'Equipe A', 'cor' => 'primary']);
        EquipeMultiplayer::create(['partida_multiplayer_id' => $partida->id, 'nome' => 'Equipe B', 'cor' => 'danger']);

        return $partida;
    }

    /** PIN único entre todas as partidas, pois as buscas por PIN não filtram status. */
    private function gerarPinUnico(): string
    {
        do {
            $pin = strtoupper(Str::random(4));
        } while (PartidaMultiplayer::where('pin', $pin)->exists());

        return $pin;
    }
}
