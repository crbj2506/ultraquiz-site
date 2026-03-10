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
            $request->session()->forget('partida_state');
        } elseif($request->session()->missing('partida_state')) {
            //Houve request [GET] mas a partida expirou
            return redirect()->route('partida.index');
        }

        // Se session partida_state existir, restaura
        if($request->session()->exists('partida_state')){
            $this->partida->restoreState($request->session()->get('partida_state'));

        //Se não, cria partida
        }else{
            $this->partida->criar();
        }

        if($request->all() && isset($request->all('resposta')['resposta'])){
            // Havendo request [POST] grava resposta dada na questao
            $questaoRespondida = $this->partida->questoes->find($request->questao);
            if (! $questaoRespondida) {
                return redirect()->route('partida.index');
            }

            $resposta_id = $questaoRespondida->respAnt = $request->all('resposta')['resposta'];

            //Atualiza Placar
            $this->partida->atualizaPlacar();

            // Elo Progression (Fase 16) e Atualização das Estatísticas da Questão
            if (auth()->check() && empty(auth()->user()->is_guest)) {
                $acertou = ($resposta_id == 0);
                auth()->user()->adicionarExperienciaBaseadoNaQuestao($questaoRespondida, $acertou);
                
                if ($acertou) {
                    $questaoRespondida->increment('acertos');
                } else {
                    $questaoRespondida->increment('erros');
                }
            }

            // Armazena Estatística. Se resposta for 0 (correta), deve-se armazenar NULL por causa do relacionamento 
            if($resposta_id == 0){$resposta_id = null;}
            $estatistica = new Estatistica();
            $estatistica->create( ['questao_id' => $request->questao, 'resposta_id' => $resposta_id]);
        }

        //Define questão a ser apresentada
        $questao = $this->partida->defineQuestao($request->questao);
        if (! $questao) {
            $request->session()->forget('partida_state');
            $request->session()->forget('partida');

            return redirect()->route('partida.index')->with('status', 'Não foi possível iniciar a partida. Verifique se existem questões suficientes e aprovadas.');
        }

        //Grava a partida em sessão de forma leve (Apenas Inteiros e IDs)
        $request->session()->put('partida_state', $this->partida->getState());

        return view('partida.index',['questao' => $questao,'partida' => $this->partida]);
    }
}
