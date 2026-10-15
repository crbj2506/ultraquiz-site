<?php

namespace Tests;

use App\Models\EquipeMultiplayer;
use App\Models\JogadorPartida;
use App\Models\PartidaMultiplayer;
use App\Models\Permissao;
use App\Models\Questao;
use App\Models\Resposta;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function criarUsuario(array $attrs = []): User
    {
        return User::factory()->create($attrs);
    }

    protected function criarSala(?User $anfitriao = null, string $status = 'waiting', string $pin = 'ABCD'): PartidaMultiplayer
    {
        $anfitriao ??= $this->criarUsuario();

        $partida = PartidaMultiplayer::create([
            'pin' => $pin,
            'status' => $status,
            'user_id' => $anfitriao->id,
        ]);
        EquipeMultiplayer::create(['partida_multiplayer_id' => $partida->id, 'nome' => 'Equipe A', 'cor' => 'primary']);
        EquipeMultiplayer::create(['partida_multiplayer_id' => $partida->id, 'nome' => 'Equipe B', 'cor' => 'danger']);

        return $partida->fresh(['equipes']);
    }

    protected function equipe(PartidaMultiplayer $partida, string $nome): EquipeMultiplayer
    {
        return EquipeMultiplayer::where('partida_multiplayer_id', $partida->id)->where('nome', $nome)->firstOrFail();
    }

    protected function colocarNaEquipe(PartidaMultiplayer $partida, EquipeMultiplayer $equipe, ?User $user = null): User
    {
        $user ??= $this->criarUsuario();
        JogadorPartida::create([
            'partida_multiplayer_id' => $partida->id,
            'equipe_multiplayer_id' => $equipe->id,
            'user_id' => $user->id,
        ]);

        return $user;
    }

    /** Cria questões já aprovadas (autor administrador e 5 alternativas erradas). */
    protected function criarQuestoesAprovadas(int $quantidade): void
    {
        $admin = $this->criarUsuario();
        $permissao = Permissao::firstOrCreate(['permissao' => 'Administrador']);
        $admin->permissoes()->attach($permissao->id);

        $inicio = Questao::count();
        for ($i = $inicio + 1; $i <= $inicio + $quantidade; $i++) {
            $questao = Questao::create([
                'pergunta' => "Pergunta de teste numero $i para o quiz",
                'resposta' => "Resposta correta $i",
                'fonte' => 'Fonte de teste',
                'user_id' => $admin->id,
            ]);
            for ($j = 1; $j <= 5; $j++) {
                Resposta::create(['questao_id' => $questao->id, 'alternativa' => "Errada $i.$j"]);
            }
        }
    }
}
