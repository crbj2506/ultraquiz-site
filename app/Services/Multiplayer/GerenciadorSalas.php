<?php

namespace App\Services\Multiplayer;

use Ratchet\ConnectionInterface;

/**
 * Estado em memória das salas ativas do servidor WebSocket.
 *
 * Cada sala guarda: conns (SplObjectStorage conexão => user_id), votos_detalhados
 * [equipe_id => [user_id => ['alt', 'time']]], votos_jogadores [user_id => true],
 * player_equipe [user_id => equipe_id], resultados, rematch_votos [equipe_id => true],
 * status (idle|playing|waiting_next), além de timer e tempo_restante.
 */
class GerenciadorSalas
{
    public array $salas = [];

    public function existe(string $pin): bool
    {
        return isset($this->salas[$pin]);
    }

    public function garantir(string $pin): void
    {
        if (!isset($this->salas[$pin])) {
            $this->salas[$pin] = [
                'conns' => new \SplObjectStorage,
                'votos_detalhados' => [],
                'votos_jogadores' => [],
                'player_equipe' => [],
                'resultados' => [],
                'rematch_votos' => [],
                'status' => 'idle',
            ];
        }
    }

    public function get(string $pin, string $chave, $padrao = null)
    {
        return $this->salas[$pin][$chave] ?? $padrao;
    }

    public function set(string $pin, string $chave, $valor): void
    {
        $this->salas[$pin][$chave] = $valor;
    }

    public function remover(string $pin, string $chave): void
    {
        unset($this->salas[$pin][$chave]);
    }

    public function anexar(string $pin, ConnectionInterface $conn, int $userId): void
    {
        $this->salas[$pin]['conns']->offsetSet($conn, $userId);
    }

    /** Identidade da conexão na sala, ou null se ela não fez join. */
    public function usuarioDaConexao(string $pin, ConnectionInterface $conn): ?int
    {
        if (!isset($this->salas[$pin]) || !$this->salas[$pin]['conns']->offsetExists($conn)) {
            return null;
        }

        return $this->salas[$pin]['conns'][$conn];
    }

    public function definirEquipe(string $pin, int $userId, int $equipeId): void
    {
        $this->salas[$pin]['player_equipe'][$userId] = $equipeId;
    }

    public function equipeDoJogador(string $pin, int $userId): ?int
    {
        return $this->salas[$pin]['player_equipe'][$userId] ?? null;
    }

    /** Equipes que têm jogadores conectados na sala. */
    public function equipesAtivas(string $pin): array
    {
        return array_values(array_unique(array_values($this->salas[$pin]['player_equipe'] ?? [])));
    }

    /** Equipes que têm jogadores conectados ou que já votaram na rodada atual. */
    public function equipesDaRodada(string $pin): array
    {
        return array_values(array_unique(array_merge(
            $this->equipesAtivas($pin),
            array_keys($this->salas[$pin]['votos_detalhados'] ?? [])
        )));
    }

    public function novaRodada(string $pin): void
    {
        $this->salas[$pin]['votos_detalhados'] = [];
        $this->salas[$pin]['votos_jogadores'] = [];
        $this->salas[$pin]['status'] = 'playing';
    }

    public function registrarVoto(string $pin, int $equipeId, int $userId, $alternativa): void
    {
        $this->salas[$pin]['votos_detalhados'][$equipeId][$userId] = [
            'alt' => $alternativa,
            'time' => microtime(true),
        ];
        $this->salas[$pin]['votos_jogadores'][$userId] = true;
    }

    /** @return int[] jogadores conectados que pertencem a uma equipe */
    public function jogadoresConectados(string $pin): array
    {
        $ids = [];
        $conns = $this->salas[$pin]['conns'];
        foreach ($conns as $conn) {
            $uid = $conns[$conn];
            if ($uid && isset($this->salas[$pin]['player_equipe'][$uid])) {
                $ids[] = $uid;
            }
        }

        return array_values(array_unique($ids));
    }

    public function todosVotaram(string $pin): bool
    {
        $conectados = $this->jogadoresConectados($pin);

        foreach ($conectados as $uid) {
            if (!isset($this->salas[$pin]['votos_jogadores'][$uid])) {
                return false;
            }
        }

        return count($conectados) > 0;
    }

    public function registrarRevanche(string $pin, int $equipeId): void
    {
        $this->salas[$pin]['rematch_votos'][$equipeId] = true;
    }

    public function equipesQueVotaramRevanche(string $pin): array
    {
        return array_keys(array_filter($this->salas[$pin]['rematch_votos']));
    }

    public function todasAceitaramRevanche(string $pin): bool
    {
        $ativas = $this->equipesAtivas($pin);
        foreach ($ativas as $eid) {
            if (!isset($this->salas[$pin]['rematch_votos'][$eid])) {
                return false;
            }
        }

        return count($ativas) > 1;
    }

    public function reiniciarParaRevanche(string $pin): void
    {
        $this->salas[$pin]['votos_detalhados'] = [];
        $this->salas[$pin]['votos_jogadores'] = [];
        $this->salas[$pin]['resultados'] = [];
        $this->salas[$pin]['rematch_votos'] = [];
    }

    /** Remove a conexão das salas; a equipe do jogador só é esquecida se ele não tiver outra aba. */
    public function desconectar(ConnectionInterface $conn): void
    {
        foreach ($this->salas as $pin => $sala) {
            if (!$sala['conns']->offsetExists($conn)) {
                continue;
            }

            $userId = $sala['conns'][$conn];
            $sala['conns']->offsetUnset($conn);

            $outraAba = false;
            foreach ($sala['conns'] as $outra) {
                if ($sala['conns'][$outra] === $userId) {
                    $outraAba = true;
                    break;
                }
            }
            if (!$outraAba) {
                unset($this->salas[$pin]['player_equipe'][$userId]);
            }
        }
    }

    public function broadcast(string $pin, array $dados, ?ConnectionInterface $exceto = null): int
    {
        if (!isset($this->salas[$pin])) {
            return 0;
        }

        $enviados = 0;
        foreach ($this->salas[$pin]['conns'] as $cliente) {
            if ($cliente === $exceto) {
                continue;
            }
            $cliente->send(json_encode($dados));
            $enviados++;
        }

        return $enviados;
    }
}
