<?php

namespace App\Http\Controllers;

use App\Models\Estatistica;
use App\Models\Questao;
use App\Models\Resposta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class QuestaoController extends Controller
{
    public $questao;
    
    public function __construct(Questao $questao){
        $this->questao = $questao;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        //
        $questoes = $this->questao->paginate(10);
        return view('questao.index',['questoes' => $questoes]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        //
        return view('questao.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $request->validate($this->questao->rules($id = null),$this->questao->feedback());
        $questao = $this->questao->create($request->all());
        return redirect()->route('questao.show', ['questao' => $questao->id]);
    }

    /**
     * Display the specified resource.
     *
     * @param  Integer $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        //
        $questao = $this->questao->find($id);
        $idQuestaoAnterior = $this->questao->where('id', '<', $id)->max('id');
        $idQuestaoPosterior = $this->questao->where('id', '>', $id)->min('id');
        return view('questao.show', ['questao' => $questao, 'idQuestaoAnterior' => $idQuestaoAnterior, 'idQuestaoPosterior' => $idQuestaoPosterior]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Questao  $questao
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Questao $questao)
    {
        //
        $questaoAnterior = $this->questao->where('id', '<', $questao->id)->get()->last();
        $questaoPosterior = $this->questao->where('id', '>', $questao->id)->get()->first();
        return view('questao.edit', ['questao' => $questao, 'questaoAnterior' => $questaoAnterior, 'questaoPosterior' => $questaoPosterior]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Integer $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $request->validate($this->questao->rules($id),$this->questao->feedback());
        $questao = $this->questao->find($id);
        $questao->update($request->all());
        return redirect()->route('questao.show', ['questao' => $questao->id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Integer $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $questao = $this->questao->find($id);
        $questao->delete();
        return redirect()->route('questao.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function principal()
    {
        //
        // forma um ARRAY com as últimas perguntas feitas
        $recentes = Estatistica::limit($this->questao->count() - ($this->questao->count() / 4))->orderBy('id', 'desc')->get()->pluck('questao_id')->all();
        $questao = null;
        while ($questao == null) {
            $id = rand(1,$this->questao->count());
            $questao = $this->questao->find($id);
            //Descartar questões recentes e com menos de 4 alternativas 
            if($questao->respostas->count() < 4 || (in_array($id, $recentes))){
                $questao = null;
            }
        }
        $respostaCorreta = new Resposta();
        $respostaCorreta->id = 0;
        $respostaCorreta->alternativa = $questao->resposta;
        //Embaralha todas as alternativas possíveis e pega apenas 4
        $questao->respostas = $questao->respostas->shuffle()->take(4);
        //Insere a alternativa CORRETA
        $questao->respostas[] = $respostaCorreta;
        //Embaralha as 5 alternativas 
        $questao->respostas = $questao->respostas->shuffle();
        return view('principal.index',['questao' => $questao]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function verifica(Request $request)
    {
        //
        $questao = $this->questao->find($request['questao_id']);
        $questao->respAnt = $request['resposta'];
      
        $respostas = Resposta::where('questao_id',$request['questao_id'])->get();

        $respostaCorreta = new Resposta();
        $respostaCorreta->id = 0;
        $respostaCorreta->alternativa = $questao->resposta;

        if($request['alternativa_0']){
            $respostas[0] = Resposta::where('id',$request['alternativa_0'])->get()[0];
        }else{
            $respostas[0] = $respostaCorreta;
        }
        if($request['alternativa_1']){
            $respostas[1] = Resposta::where('id',$request['alternativa_1'])->get()[0];
        }else{
            $respostas[1] = $respostaCorreta;
        }
        if($request['alternativa_2']){
            $respostas[2] = Resposta::where('id',$request['alternativa_2'])->get()[0];
        }else{
            $respostas[2] = $respostaCorreta;
        }
        if($request['alternativa_3']){
            $respostas[3] = Resposta::where('id',$request['alternativa_3'])->get()[0];
        }else{
            $respostas[3] = $respostaCorreta;
        }
        if($request['alternativa_4']){
            $respostas[4] = Resposta::where('id',$request['alternativa_4'])->get()[0];
        }else{
            $respostas[4] = $respostaCorreta;
        }
        $questao->respostas = $respostas;

        $resposta_id = null;
        if($questao->getAttributes()['respAnt'] != '0'){
            $resposta_id = $questao->getAttributes()['respAnt'];
        }
        
        $estatistica = new Estatistica();
        $estatistica->create( ['questao_id' => $questao->getAttributes()['id'], 'resposta_id' => $resposta_id ]);

        return view('principal.index',['questao' => $questao]);
    }
    public function estatistica(Request $request)
    {
        $estatisticas = Estatistica::orderBy('id', 'desc')->paginate(50);
        //dd($estatisticas);
        return view('principal.estatistica',['estatisticas' => $estatisticas]);
    }
}
