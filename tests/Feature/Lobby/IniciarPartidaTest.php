<?php

namespace Tests\Feature\Lobby;

use App\Models\PartidaMultiplayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IniciarPartidaTest extends TestCase
{
    use RefreshDatabase;

    private function prepararSalaCompleta(): array
    {
        $anfitriao = $this->criarUsuario();
        $partida = $this->criarSala($anfitriao);
        $this->colocarNaEquipe($partida, $this->equipe($partida, 'Equipe A'), $anfitriao);
        $this->colocarNaEquipe($partida, $this->equipe($partida, 'Equipe B'));

        return [$anfitriao, $partida];
    }

    public function test_apenas_o_anfitriao_pode_iniciar(): void
    {
        [, $partida] = $this->prepararSalaCompleta();

        $this->actingAs($this->criarUsuario())
            ->from(route('lobby.sala', ['pin' => $partida->pin]))
            ->post(route('lobby.iniciar', ['pin' => $partida->pin]))
            ->assertSessionHas('error');

        $this->assertSame('waiting', $partida->fresh()->status);
    }

    public function test_nao_inicia_com_equipe_vazia(): void
    {
        $anfitriao = $this->criarUsuario();
        $partida = $this->criarSala($anfitriao);
        $this->colocarNaEquipe($partida, $this->equipe($partida, 'Equipe A'), $anfitriao);

        $this->actingAs($anfitriao)
            ->from(route('lobby.sala', ['pin' => $partida->pin]))
            ->post(route('lobby.iniciar', ['pin' => $partida->pin]))
            ->assertSessionHas('error');

        $this->assertSame('waiting', $partida->fresh()->status);
    }

    public function test_inicio_com_sucesso_grava_status_e_questoes(): void
    {
        config(['app.numero_questoes_multiplayer' => null, 'app.numero_questoes_multiplayer_dev' => 3]);
        $this->criarQuestoesAprovadas(8);
        [$anfitriao, $partida] = $this->prepararSalaCompleta();

        $this->actingAs($anfitriao)
            ->postJson(route('lobby.iniciar', ['pin' => $partida->pin]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $partida = $partida->fresh();
        $this->assertSame('playing', $partida->status);
        $this->assertSame(0, (int) $partida->pergunta_atual_index);
        $this->assertCount(3, json_decode($partida->questoes_json, true));
    }

    public function test_inicio_zera_a_pontuacao_das_equipes(): void
    {
        $this->criarQuestoesAprovadas(8);
        [$anfitriao, $partida] = $this->prepararSalaCompleta();
        $this->equipe($partida, 'Equipe A')->update(['pontuacao' => 30]);

        $this->actingAs($anfitriao)->postJson(route('lobby.iniciar', ['pin' => $partida->pin]))->assertOk();

        $this->assertSame(0, (int) $this->equipe($partida, 'Equipe A')->pontuacao);
    }

    public function test_override_de_questoes_tem_prioridade_sobre_o_ambiente(): void
    {
        config(['app.numero_questoes_multiplayer' => '2']);
        $this->criarQuestoesAprovadas(8);
        [$anfitriao, $partida] = $this->prepararSalaCompleta();

        $this->actingAs($anfitriao)->postJson(route('lobby.iniciar', ['pin' => $partida->pin]))->assertOk();

        $this->assertCount(2, json_decode($partida->fresh()->questoes_json, true));
    }

    public function test_ambiente_de_producao_usa_a_quantidade_de_producao(): void
    {
        config(['app.numero_questoes_multiplayer' => null, 'app.numero_questoes_multiplayer_prod' => 4]);
        $this->app['env'] = 'production';
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->criarQuestoesAprovadas(10);
        [$anfitriao, $partida] = $this->prepararSalaCompleta();

        $this->actingAs($anfitriao)->postJson(route('lobby.iniciar', ['pin' => $partida->pin]))->assertOk();

        $this->assertLessThanOrEqual(4, count(json_decode($partida->fresh()->questoes_json, true)));
    }

    public function test_nao_reinicia_partida_em_andamento_nem_encerrada(): void
    {
        foreach (['playing', 'finished'] as $i => $status) {
            $anfitriao = $this->criarUsuario();
            $pin = 'ST' . $i . 'X';
            $partida = $this->criarSala($anfitriao, $status, $pin);
            $this->colocarNaEquipe($partida, $this->equipe($partida, 'Equipe A'), $anfitriao);
            $this->colocarNaEquipe($partida, $this->equipe($partida, 'Equipe B'));
            $this->equipe($partida, 'Equipe A')->update(['pontuacao' => 20]);

            $this->actingAs($anfitriao)
                ->from(route('lobby.sala', ['pin' => $pin]))
                ->post(route('lobby.iniciar', ['pin' => $pin]))
                ->assertSessionHas('error');

            $this->assertSame($status, $partida->fresh()->status);
            $this->assertNull($partida->fresh()->questoes_json);
            $this->assertSame(20, (int) $this->equipe($partida, 'Equipe A')->pontuacao);
        }
    }

    public function test_jogar_exige_partida_em_andamento_e_informa_a_equipe_do_jogador(): void
    {
        [$anfitriao, $partida] = $this->prepararSalaCompleta();

        $this->actingAs($anfitriao)->get(route('lobby.jogar', ['pin' => $partida->pin]))->assertNotFound();

        PartidaMultiplayer::whereKey($partida->id)->update(['status' => 'playing']);
        $this->actingAs($anfitriao)->get(route('lobby.jogar', ['pin' => $partida->pin]))
            ->assertOk()
            ->assertViewHas('minhaEquipeId', $this->equipe($partida, 'Equipe A')->id);
    }
}
