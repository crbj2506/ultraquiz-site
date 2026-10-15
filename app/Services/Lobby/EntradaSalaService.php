<?php

namespace App\Services\Lobby;

use App\Models\PartidaMultiplayer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EntradaSalaService
{
    /** Localiza a sala pelo PIN e garante que ainda aceita jogadores. */
    public function localizar(string $pin, string $mensagemEncerrada): PartidaMultiplayer
    {
        $partida = PartidaMultiplayer::where('pin', strtoupper($pin))->first();

        if (!$partida) {
            throw new LobbyException('Sala não encontrada. Verifique o PIN e tente novamente.');
        }
        if ($partida->status === 'finished') {
            throw new LobbyException($mensagemEncerrada);
        }

        return $partida;
    }

    public function criarConvidado(string $apelido): User
    {
        return User::create([
            'name' => $apelido . ' (Convidado)',
            'email' => 'guest_' . uniqid() . '@ultraquiz.com',
            'password' => Hash::make(Str::random(16)),
            'is_guest' => true,
            'email_verified_at' => now(),
        ]);
    }
}
