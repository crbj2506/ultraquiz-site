<?php

namespace App\Http\Controllers;

use App\Models\Resposta;
use Illuminate\Http\Request;

class RespostaController extends Controller
{
    public function __construct(Resposta $resposta){
        $this->resposta = $resposta;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $respostas = $this->resposta->paginate(10);
        return view('resposta.index',['respostas' => $respostas]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('resposta.create');
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

        //dd($request->all());
        $request->validate($this->resposta->rules($id = null),$this->resposta->feedback());
        $resposta = $this->resposta->create($request->all());
        //return redirect()->route('resposta.show', ['resposta' => $resposta->id]);
        return redirect()->route('questao.edit', ['questao' => $resposta->questao_id]);
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
        $resposta = $this->resposta->find($id);
        return view('resposta.show', ['resposta' => $resposta]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Resposta  $resposta
     * @return \Illuminate\Http\Response
     */
    public function edit(Resposta $resposta)
    {
        //
        return view('resposta.edit', ['resposta' => $resposta]);
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
        $request->validate($this->resposta->rules($id),$this->resposta->feedback());
        $resposta = $this->resposta->find($id);
        $resposta->update($request->all());
        return redirect()->route('questao.show', ['questao' => $resposta->questao_id]);
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
        $resposta = $this->resposta->find($id);
        $resposta->delete();
        return redirect()->route('resposta.index');
    }
}
