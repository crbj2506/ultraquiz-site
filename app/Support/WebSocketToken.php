<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Token que prova ao servidor WebSocket quem é o usuário autenticado na web.
 * É criptografado (e autenticado) com a APP_KEY, vinculado a um PIN e expira.
 */
class WebSocketToken
{
    public const VALIDADE_SEGUNDOS = 6 * 3600;

    public static function emitir(int|string $userId, string $pin): string
    {
        return Crypt::encryptString(json_encode([
            'u' => (int) $userId,
            'p' => strtoupper(trim($pin)),
            'e' => time() + self::VALIDADE_SEGUNDOS,
        ]));
    }

    /** Retorna o id do usuário se o token for válido para o PIN informado; senão, null. */
    public static function validar(?string $token, ?string $pin): ?int
    {
        if (!$token || !$pin) {
            return null;
        }

        try {
            $dados = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            return null;
        }

        if (!is_array($dados) || !isset($dados['u'], $dados['p'], $dados['e'])) {
            return null;
        }
        if ($dados['p'] !== strtoupper(trim($pin)) || $dados['e'] < time()) {
            return null;
        }

        return (int) $dados['u'];
    }
}
