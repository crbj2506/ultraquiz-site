<?php

namespace App\Http\Controllers;

use App\Models\Questao;
use App\Models\Resposta;
use App\Models\Verificacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class SugestaoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        //
        $sugestoes = Questao::paginate(10);
        return view('sugestao.crud',['sugestoes' => $sugestoes]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        //
        return view('sugestao.crud');
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
        // Valida 
        $request->validate(Questao::rulesSugestao($id = null),Questao::feedback());

        // Insere no Request o user_id
        $request->merge(['user_id' => Auth::id()]);

        // Cria uma sugestão
        $sugestao = Questao::create($request->all());

        // Formata o Array de Alternativas
        foreach ($request->alternativas as $key => $alternativa) {
            $alternativas[$key]['alternativa'] = $alternativa;
            $alternativas[$key]['questao_id'] = $sugestao->id;
        }

        // Cria no Banco as Alternativas da Sugestão
        $sugestao->respostas()->createMany($alternativas);

        return redirect()->route('sugestao.show', ['sugestao' => $sugestao->id]);
    }

    /**
     * Display the specified resource.
     *
     * @param  Integer $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show($sugestao)
    {
        //
        $sugestao = Questao::find($sugestao);
        if(Route::current()->action['as'] == "sugestao.show"){
            $sugestao->show = true;
        };
        return view('sugestao.crud', ['sugestao' => $sugestao]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Questao  $sugestao
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Questao $sugestao)
    {
        //
        if(Route::current()->action['as'] == "sugestao.edit"){
            $sugestao->edit = true;
        };
        return view('sugestao.crud', ['sugestao' => $sugestao]);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  Integer  $id
     * @return \Illuminate\Contracts\View\View
     */
    public function editarSugestaoPorMim($id)
    {
        //
        $sugestao = Questao::find($id);
        if($sugestao->user_id != Auth::id() || $sugestao->verificacoes->count() > 0){
            return view('auth.acesso-negado');
        }
        if(Route::current()->action['as'] == "sugestao.edit"){
            $sugestao->edit = true;
        };
        return view('sugestao.editarpormim', ['sugestao' => $sugestao]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Integer $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $sugestao)
    {
        //
        $request->validate(Questao::rulesSugestao($sugestao),Questao::feedback());
        $sugestao = Questao::find($sugestao);
        $sugestao->update($request->all());

        $resposta[1] = Resposta::find($sugestao->respostas[0]->id);
        $resposta[2] = Resposta::find($sugestao->respostas[1]->id);
        $resposta[3] = Resposta::find($sugestao->respostas[2]->id);
        $resposta[4] = Resposta::find($sugestao->respostas[3]->id);
        $resposta[1]->update(['alternativa' => $request->alternativas[0]]);
        $resposta[2]->update(['alternativa' => $request->alternativas[1]]);
        $resposta[3]->update(['alternativa' => $request->alternativas[2]]);
        $resposta[4]->update(['alternativa' => $request->alternativas[3]]);

        return redirect()->route('sugestao.show', ['sugestao' => $sugestao]);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Integer $id
     * @return \Illuminate\Http\Response
     */
    public function atualizarSugestaoPorMim(Request $request, $sugestao)
    {
        //
        $request->validate(Questao::rulesSugestao($sugestao),Questao::feedback());
        $sugestao = Questao::find($sugestao);
        //dd($sugestao, $request->all());//
        $sugestao->update($request->all());

        $resposta[1] = Resposta::find($sugestao->respostas[0]->id);
        $resposta[2] = Resposta::find($sugestao->respostas[1]->id);
        $resposta[3] = Resposta::find($sugestao->respostas[2]->id);
        $resposta[4] = Resposta::find($sugestao->respostas[3]->id);
        $resposta[1]->update(['alternativa' => $request->alternativas[0]]);
        $resposta[2]->update(['alternativa' => $request->alternativas[1]]);
        $resposta[3]->update(['alternativa' => $request->alternativas[2]]);
        $resposta[4]->update(['alternativa' => $request->alternativas[3]]);

        return redirect()->route('sugestaopormim.mostrar', ['sugestao' => $sugestao]);
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
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function listarSugestoes()
    {
        //
        $sugestoes = Questao::where('questoes.user_id','!=', Auth::id())
            ->select('questoes.*', DB::raw( '(SELECT COUNT(aprovada) from verificacoes where questao_id = questoes.id group by questao_id ) as verificacoe' ), DB::raw( '(SELECT MAX(permissao_id) from permissoes_users where user_id = questoes.user_id) as permissao' ))
            ->distinct()
            ->having('permissao', '!=', '3')
            ->orderBy('verificacoe')
            ->paginate(10)
            //->get()
            ;
        //dd($sugestoes, Questao::find('205'));
        return view('sugestao.sugestoes',['sugestoes' => $sugestoes]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function listarSugestoesPorMim()
    {
        //
        $sugestoes = Questao::where('questoes.user_id','=', Auth::id())
            ->select('questoes.*', DB::raw( '(SELECT COUNT(aprovada) from verificacoes where questao_id = questoes.id group by questao_id ) as verificacoe' ), DB::raw( '(SELECT MAX(permissao_id) from permissoes_users where user_id = questoes.user_id) as permissao' ))
            ->distinct()
            //->having('permissao', '!=', '3')
            ->orderByDesc('questoes.id')
            ->paginate(10)
            //->get()
            ;
        //dd($sugestoes, Questao::find('205'));
        return view('sugestao.sugestoespormim',['sugestoes' => $sugestoes]);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Questao  $sugestao
     * @return \Illuminate\Contracts\View\View
     */
    public function mostrarSugestao($sugestao)
    {
        //
        $sugestao = Questao::find($sugestao);
        return view('sugestao.mostrar',['sugestao' => $sugestao]);
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Questao  $sugestao
     * @return \Illuminate\Contracts\View\View
     */
    public function mostrarSugestaoPorMim($sugestao)
    {
        //
        $sugestao = Questao::find($sugestao);
        return view('sugestao.mostrarpormim',['sugestao' => $sugestao]);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Questao  $sugestao
     * @return \Illuminate\Contracts\View\View
     */
    public function aprovarSugestao(Request $request, $sugestao)
    {
        //
        $sugestao = Questao::find($sugestao);
        if($sugestao->user_id == Auth::id()){
            return view('auth.acesso-negado');
        }
        $verificacao = Verificacao::where('user_id', Auth::id())->where('questao_id', $sugestao->id);

        if ($verificacao->get()->isEmpty()) {
            $verificacao = Verificacao::create(['user_id' => Auth::id(), 'questao_id' => $sugestao->id, 'aprovada' => $request->verificacao]);
        }else{
            $verificacao->update(['aprovada' => $request->verificacao]);
        }

        return view('sugestao.mostrar',['sugestao' => $sugestao]);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function criarSugestaoPorMim()
    {
        //
        return view('sugestao.criarpormim');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function armazenarSugestao(Request $request)
    {
        //
        // Valida 
        $request->validate(Questao::rulesSugestao($id = null),Questao::feedback());

        // Insere no Request o user_id
        $request->merge(['user_id' => Auth::id()]);

        // Cria uma sugestão
        $sugestao = Questao::create($request->all());

        // Formata o Array de Alternativas
        foreach ($request->alternativas as $key => $alternativa) {
            $alternativas[$key]['alternativa'] = $alternativa;
            $alternativas[$key]['questao_id'] = $sugestao->id;
        }

        // Cria no Banco as Alternativas da Sugestão
        $sugestao->respostas()->createMany($alternativas);

        return redirect()->route('sugestao.mostrar', ['sugestao' => $sugestao->id]);
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function armazenarSugestaoPorMim(Request $request)
    {
        //
        // Valida 
        $request->validate(Questao::rulesSugestao($id = null),Questao::feedback());

        // Insere no Request o user_id
        $request->merge(['user_id' => Auth::id()]);

        // Cria uma sugestão
        $sugestao = Questao::create($request->all());

        // Formata o Array de Alternativas
        foreach ($request->alternativas as $key => $alternativa) {
            $alternativas[$key]['alternativa'] = $alternativa;
            $alternativas[$key]['questao_id'] = $sugestao->id;
        }

        // Cria no Banco as Alternativas da Sugestão
        $sugestao->respostas()->createMany($alternativas);

        return redirect()->route('sugestaopormim.mostrar', ['sugestao' => $sugestao->id]);
    }

}
