<?php

namespace App\Http\Controllers;

use App\Models\Questao;
use App\Models\Resposta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class QuestaoController extends Controller
{
    public function __construct(Questao $questao){
        $this->questao = $questao;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        $questao = $this->questao->find($id);
        return view('questao.show', ['questao' => $questao]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Questao  $questao
     * @return \Illuminate\Http\Response
     */
    public function edit(Questao $questao)
    {
        //
        return view('questao.edit', ['questao' => $questao]);
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
    public function principal()
    {
        //
        $questao = null;
        while ($questao == null) {
            $id = rand(1,$this->questao->count());
            $questao = $this->questao->find($id);
        }
        $respostaCorreta = new Resposta();
        $respostaCorreta->id = 0;
        $respostaCorreta->alternativa = $questao->resposta;

        $questao->respostas[] = $respostaCorreta;
        $questao->respostas = $questao->respostas->shuffle();

        //dd($questao);
        return view('principal.index',['questao' => $questao]);
    }
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

        return view('principal.index',['questao' => $questao]);
    }
}
