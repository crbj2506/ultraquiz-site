<?php

namespace Tests\Feature\Multiplayer;

use App\Support\WebSocketToken;
use Illuminate\Support\Facades\Crypt;

class AutenticacaoWebSocketTest extends ServidorWebSocketTestCase
{
    public function test_token_valido_identifica_o_usuario(): void
    {
        $token = WebSocketToken::emitir(7, 'abcd');

        $this->assertSame(7, WebSocketToken::validar($token, 'ABCD'));
    }

    public function test_token_rejeitado_para_outro_pin_expirado_adulterado_ou_ausente(): void
    {
        $this->assertNull(WebSocketToken::validar(WebSocketToken::emitir(7, 'ABCD'), 'ZZZZ'));
        $this->assertNull(WebSocketToken::validar('lixo', 'ABCD'));
        $this->assertNull(WebSocketToken::validar(null, 'ABCD'));
        $this->assertNull(WebSocketToken::validar(WebSocketToken::emitir(7, 'ABCD'), null));

        $expirado = Crypt::encryptString(json_encode(['u' => 7, 'p' => 'ABCD', 'e' => time() - 1]));
        $this->assertNull(WebSocketToken::validar($expirado, 'ABCD'));
    }

    public function test_join_sem_token_ou_com_user_id_forjado_e_recusado(): void
    {
        $partida = $this->salaEmAndamento();
        $vitima = $this->colocarNaEquipe($partida, $this->equipe($partida, 'Equipe A'));
        $atacante = $this->conexao(1);

        $this->enviar($atacante, ['action' => 'join', 'pin' => 'ABCD', 'user_id' => $vitima->id]);
        $this->enviar($atacante, ['action' => 'join', 'pin' => 'ABCD', 'token' => 'forjado']);

        $this->assertSame(['auth_error', 'auth_error'], $atacante->tipos());
        $this->assertArrayNotHasKey('ABCD', $this->lerPropriedade('salas'));
    }

    public function test_token_de_outra_sala_nao_permite_entrar(): void
    {
        $partida = $this->salaEmAndamento();
        $jogador = $this->colocarNaEquipe($partida, $this->equipe($partida, 'Equipe A'));
        $conn = $this->conexao(1);

        $this->enviar($conn, ['action' => 'join', 'pin' => 'ABCD', 'token' => WebSocketToken::emitir($jogador->id, 'OUTR')]);

        $this->assertSame(['auth_error'], $conn->tipos());
    }

    public function test_acoes_de_conexao_que_nao_fez_join_sao_ignoradas(): void
    {
        $partida = $this->salaEmAndamento();
        $a = $this->equipe($partida, 'Equipe A');
        $jogador = $this->colocarNaEquipe($partida, $a);
        $legitimo = $this->conexao(1);
        $intruso = $this->conexao(2);
        $this->entrar($legitimo, $jogador, 'ABCD');

        $this->enviar($intruso, ['action' => 'votar', 'pin' => 'ABCD', 'user_id' => $jogador->id, 'alternativa_id' => 0]);
        $this->enviar($intruso, ['action' => 'start', 'pin' => 'ABCD', 'user_id' => $partida->user_id]);
        $this->enviar($intruso, ['action' => 'rematch_vote', 'pin' => 'ABCD', 'user_id' => $jogador->id]);

        $this->assertNull($legitimo->ultima('voto'));
        $this->assertNull($legitimo->ultima('redirect'));
        $this->assertNull($legitimo->ultima('rematch_status'));
        $this->assertSame([], $intruso->recebidas);
    }

    public function test_user_id_enviado_pelo_cliente_e_ignorado_nos_votos(): void
    {
        $partida = $this->salaEmAndamento();
        $a = $this->equipe($partida, 'Equipe A');
        $b = $this->equipe($partida, 'Equipe B');
        $u1 = $this->colocarNaEquipe($partida, $a);
        $u2 = $this->colocarNaEquipe($partida, $b);
        $c1 = $this->conexao(1);
        $c2 = $this->conexao(2);
        $this->entrar($c1, $u1, 'ABCD');
        $this->entrar($c2, $u2, 'ABCD');

        // u1 tenta votar fingindo ser u2: o voto é registrado como de u1.
        $this->enviar($c1, ['action' => 'votar', 'pin' => 'ABCD', 'user_id' => $u2->id, 'alternativa_id' => 0]);

        $voto = $c2->ultima('voto');
        $this->assertSame($u1->id, $voto['user_id']);
        $this->assertSame($a->id, $voto['equipe_id']);
    }

    public function test_apenas_o_anfitriao_pode_dar_start(): void
    {
        [$partida, , , $jogador, , $c1, $c2] = $this->prepararDoisJogadores();

        $this->enviar($c1, ['action' => 'start', 'pin' => 'ABCD']);

        $this->assertNull($c1->ultima('redirect'));
        $this->assertNull($c2->ultima('redirect'));
    }

    private function prepararDoisJogadores(): array
    {
        $partida = $this->salaEmAndamento();
        $a = $this->equipe($partida, 'Equipe A');
        $b = $this->equipe($partida, 'Equipe B');
        $u1 = $this->colocarNaEquipe($partida, $a);
        $u2 = $this->colocarNaEquipe($partida, $b);
        $c1 = $this->conexao(1);
        $c2 = $this->conexao(2);
        $this->entrar($c1, $u1, 'ABCD');
        $this->entrar($c2, $u2, 'ABCD');

        return [$partida, $a, $b, $u1, $u2, $c1, $c2];
    }
}
