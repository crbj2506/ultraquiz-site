<?php

namespace App\Http\Controllers;

use App\Models\Estatistica;
use App\Models\Partida;
use App\Models\Questao;
use App\Models\Resposta;
use Illuminate\Http\Request;

class PartidaController extends Controller
{
    public function __construct(Partida $partida){
        $this->partida = $partida;
    }
    //
    public function index(Request $request)
    {
        //
        if(!$request->questao){
            $request->session()->forget('partida');
        }

        if($request->session()->exists('partida')){//Pega a partida gravada em session se session partida já existir
            $this->partida = $request->session()->get('partida');

        }else{//Se partida ainda não exixte cria partida

            //Busca 5 Questões aleatórias
            $this->partida->questoes = Questao::all()->shuffle()->take(10);

            //Percorre as questões e monta as alternativas
            foreach ($this->partida->questoes as $indice => $questao) {
                $respostaCorreta = new Resposta();
                $respostaCorreta->id = 0;
                $respostaCorreta->alternativa = $questao->resposta;
                $this->partida->questoes[$indice]->respostas = $this->partida->questoes[$indice]->respostas->shuffle()->take(4);
                $this->partida->questoes[$indice]->respostas[] = $respostaCorreta; 
                $this->partida->questoes[$indice]->respostas = $this->partida->questoes[$indice]->respostas->shuffle();
            }
        }

        if($request->all()){
        
            $resposta_id = $this->partida->questoes->find($request->questao)->respAnt = $request->all('resposta')['resposta'];
            if($resposta_id == 0){
                $resposta_id = null;
            }
            $estatistica = new Estatistica();
            $estatistica->create( ['questao_id' => $request->questao, 'resposta_id' => $resposta_id]);
        }

        $questao = $this->partida->questoes->find($request->questao ? $request->questao : $this->partida->questoes[0]->id);
        $indice = $this->partida->indice = array_search( $questao->id, $this->partida->questoes->pluck('id')->toArray() );

        if($indice > 0 && $indice < $this->partida->questoes->count() -1){
            $qAnt = $this->partida->questoes[$indice -1];
            $qPost = $this->partida->questoes[$indice +1];

        }elseif($indice == $this->partida->questoes->count() -1){
            $qAnt = $this->partida->questoes[$indice -1];
            $qPost = null;

        }elseif($indice == 0){
            $qAnt = null;
            $qPost = $this->partida->questoes[$indice +1];

        }

        //Grava a partida em sessão
        $request->session()->put('partida', $this->partida);

        return view('partida.index',['questao' => $questao,'partida' => $this->partida, 'qAnt' => $qAnt, 'qPost' => $qPost]);
    }
}
