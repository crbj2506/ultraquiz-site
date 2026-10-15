<?php

namespace App\Services\Lobby;

use App\Models\EquipeMultiplayer;
use App\Models\PartidaMultiplayer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

class CriacaoSalaService
{
    private const MAX_TENTATIVAS = 5;

    public function criar(int $anfitriaoId): PartidaMultiplayer
    {
        // O índice único do banco é a garantia final: se duas salas sortearem o mesmo PIN
        // ao mesmo tempo, a segunda sorteia outro.
        for ($tentativa = 1; ; $tentativa++) {
            try {
                $partida = PartidaMultiplayer::create([
                    'pin' => $this->gerarPinUnico(),
                    'status' => 'waiting',
                    'user_id' => $anfitriaoId,
                ]);
                break;
            } catch (UniqueConstraintViolationException $e) {
                if ($tentativa >= self::MAX_TENTATIVAS) {
                    throw $e;
                }
            }
        }

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
