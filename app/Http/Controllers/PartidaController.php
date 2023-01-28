<?php

namespace App\Http\Controllers;

use App\Models\Estatistica;
use App\Models\Partida;
use Illuminate\Http\Request;

class PartidaController extends Controller
{
    public $partida;
    public function __construct(Partida $partida){
        $this->partida = $partida;
    }
    //
    public function index(Request $request)
    {
        //Se não houve request [GET] do ID da questão, LIMPA session partida
        if(!$request->questao){
            $request->session()->forget('partida');
        }

        // Se session partida existir, restaura
        if($request->session()->exists('partida')){
            $this->partida = $request->session()->get('partida');

        //Se não, cria partida
        }else{
            $this->partida->criar();
        }

        if($request->all()){
            // Havendo request [POST] grava resposta dada na questao
            $resposta_id = $this->partida->questoes->find($request->questao)->respAnt = $request->all('resposta')['resposta'];

            //Atualiza Placar
            $this->partida->atualizaPlacar();

            // Armazena Estatística. Se resposta for 0 (correta), deve-se armazenar NULL por causa do relacionamento 
            if($resposta_id == 0){$resposta_id = null;}
            $estatistica = new Estatistica();
            $estatistica->create( ['questao_id' => $request->questao, 'resposta_id' => $resposta_id]);
        }

        //Define questão a ser apresentada
        $questao = $this->partida->defineQuestao($request->questao);

        //Grava a partida em sessão
        $request->session()->put('partida', $this->partida);

        return view('partida.index',['questao' => $questao,'partida' => $this->partida]);
    }
}
