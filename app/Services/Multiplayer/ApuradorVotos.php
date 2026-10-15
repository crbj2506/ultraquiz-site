<?php

namespace App\Services\Multiplayer;

class ApuradorVotos
{
    /**
     * Voto final da equipe: alternativa mais votada; em empate, vence a que recebeu o voto mais rápido.
     *
     * @param array $votosEquipe [user_id => ['alt' => alternativa, 'time' => microtime]]
     */
    public static function votoDaEquipe(array $votosEquipe): ?int
    {
        if (count($votosEquipe) === 0) {
            return null;
        }

        $contagem = [];
        $primeiroTempo = [];

        foreach ($votosEquipe as $voto) {
            $alt = $voto['alt'];
            $contagem[$alt] = ($contagem[$alt] ?? 0) + 1;
            if (!isset($primeiroTempo[$alt]) || $voto['time'] < $primeiroTempo[$alt]) {
                $primeiroTempo[$alt] = $voto['time'];
            }
        }

        $maximo = max($contagem);
        $empatadas = array_keys(array_filter($contagem, fn ($qtd) => $qtd === $maximo));

        $vencedora = null;
        foreach ($empatadas as $alt) {
            if ($vencedora === null || $primeiroTempo[$alt] < $primeiroTempo[$vencedora]) {
                $vencedora = $alt;
            }
        }

        return $vencedora;
    }
}
