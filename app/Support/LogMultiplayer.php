<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Log estruturado do multiplayer. O nome do evento é a mensagem e o contexto
 * (pin, user_id, ip...) vai como dados, para facilitar buscas em produção.
 */
class LogMultiplayer
{
    public const CANAL = 'multiplayer';

    public static function debug(string $evento, array $contexto = []): void
    {
        self::registrar('debug', $evento, $contexto);
    }

    public static function info(string $evento, array $contexto = []): void
    {
        self::registrar('info', $evento, $contexto);
    }

    public static function aviso(string $evento, array $contexto = []): void
    {
        self::registrar('warning', $evento, $contexto);
    }

    public static function erro(string $evento, \Throwable $e, array $contexto = []): void
    {
        self::registrar('error', $evento, $contexto + [
            'excecao' => get_class($e),
            'mensagem' => $e->getMessage(),
            'arquivo' => basename($e->getFile()) . ':' . $e->getLine(),
        ]);
    }

    private static function registrar(string $nivel, string $evento, array $contexto): void
    {
        try {
            Log::channel(self::CANAL)->{$nivel}($evento, $contexto);
        } catch (\Throwable $e) {
            // Falha de log nunca pode derrubar o jogo.
        }
    }
}
