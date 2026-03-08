<?php

namespace App\Http\Controllers;

use App\Models\Estatistica;
use App\Models\Questao;
use App\Models\Resposta;
use App\Models\VotoQuestao;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    public function index(Request $request)
    {
        // -----------------------------------------------------------
        // Alteração: suporte a filtros de listagem de questões
        // Campos suportados (via query string / GET):
        // - f_pergunta : pesquisa por parte do texto da pergunta
        // - f_resposta  : pesquisa por parte do texto da resposta correta
        // Observação: usamos "LIKE %valor%" para correspondência parcial.
        // -----------------------------------------------------------

        // Cria uma query base a partir do model Questao
        $query = $this->questao->newQuery();

        // Aplica filtro por parte da pergunta quando informado
        if ($request->filled('f_pergunta')) {
            $query->where('pergunta', 'like', '%' . $request->input('f_pergunta') . '%');
        }

        // Aplica filtro por parte da resposta correta quando informado
        if ($request->filled('f_resposta')) {
            $query->where('resposta', 'like', '%' . $request->input('f_resposta') . '%');
        }

        // Pagina o resultado e preserva os parâmetros de query (mantém os filtros nos links de página)
        $questoes = $query->paginate(10)->appends($request->query());

        // Retorna a view com a coleção paginada
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
        $limit = max((int)($this->questao->count() * 0.75), 1);
        $recentes = Estatistica::orderBy('id', 'desc')->limit($limit)->pluck('questao_id')->toArray();

        // Tenta achar uma questão válida (com alternativas e aprovada)
        $questoes_candidatas = $this->questao->with(['respostas', 'user.permissoes', 'verificacoes'])
            ->whereNotIn('id', $recentes)
            ->has('respostas', '>=', 4)
            ->inRandomOrder()
            ->limit(15)
            ->get();

        $questao = $questoes_candidatas->filter(function($q) { return $q->verifica(); })->first();

        if (!$questao) {
            // Fallback: ignora recentes
            $questoes_candidatas = $this->questao->with(['respostas', 'user.permissoes', 'verificacoes'])
                ->has('respostas', '>=', 4)
                ->inRandomOrder()
                ->limit(15)
                ->get();
            $questao = $questoes_candidatas->filter(function($q) { return $q->verifica(); })->first();
        }

        if (!$questao) {
            return view('principal.index', ['questao' => null]);
        }

        $respostaCorreta = new Resposta();
        $respostaCorreta->id = 0;
        $respostaCorreta->alternativa = $questao->resposta;
        //Embaralha todas as alternativas possíveis e pega apenas 4
        $questao->respostas = $questao->respostas->shuffle()->take(4);
        //Insere a alternativa CORRETA
        $questao->respostas->push($respostaCorreta);
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

        // Otimização N+1: Busca todas as respostas passadas de uma única vez no banco.
        $idsAlternativas = [
            $request['alternativa_0'], $request['alternativa_1'], 
            $request['alternativa_2'], $request['alternativa_3'], $request['alternativa_4']
        ];
        $respostasBanco = Resposta::whereIn('id', array_filter($idsAlternativas))->get()->keyBy('id');

        for ($i = 0; $i < 5; $i++) {
            $alt_id = $request["alternativa_$i"];
            if ($alt_id && isset($respostasBanco[$alt_id])) {
                $respostas[$i] = clone $respostasBanco[$alt_id];
            } else {
                $respostas[$i] = clone $respostaCorreta;
            }
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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function sugestaoIndex()
    {
        //
        return view('questao.sugerir');
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function sugestaoCreate()
    {
        //
        return view('questao.sugerir');
    }

    /**
     * Store a vote (like/dislike) for a question.
     */
    public function votar(Request $request, $questaoId)
    {
        // O usuário precisa estar logado para votar (incluindo convidados)
        if (!Auth::check()) {
            return redirect()->back()->with('error', 'Você precisa estar logado para avaliar uma pergunta.');
        }

        $request->validate([
            'voto' => 'required|in:1,-1',
        ]);

        // Cria ou atualiza o voto do usuário para esta questão
        VotoQuestao::updateOrCreate(
            ['questao_id' => $questaoId, 'user_id' => Auth::id()],
            ['voto' => $request->voto]
        );

        return redirect()->back()->with('success', 'Obrigado pelo seu feedback!');
    }

}
