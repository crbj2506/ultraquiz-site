<?php

namespace App\Models;

use App\Models\Questao;
use App\Models\Resposta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Partida extends Model
{
    use HasFactory;
    public function atualizaPlacar(){
        //Atualiza Placar
        $this->a = $this->b = $this->e = null;
        foreach ($this->questoes as $key => $q) {
            if ($q->respAnt === '0') {
                $this->a++;
            } elseif ($q->respAnt === null) {
                $this->b++;
            } else {
                $this->e++;
            }
        }
    }
    public function criar(){
        // forma um ARRAY com as últimas perguntas feitas (75%)
        $this->questoes = new Collection();
        $recentes = Estatistica::limit(Questao::count()* 0.75)->orderBy('id', 'desc')->get()->pluck('questao_id')->all();

        // Sorteia as Questões da Partida
        for ($i=0; $i < env('APP_NUMERO_QUESTOES_PARTIDA'); $i++) { 
            $questao = null;
            while ($questao == null) {
                $id = rand(1,Questao::count());
                $questao = Questao::find($id);

                //Descartar questões recentes, com menos de 4 alternativas e que já tenham sido selecionadas para a partida
                if($questao->respostas->count() < 4 || (in_array($id, $recentes) || in_array($id, $this->questoes->pluck('id')->all()))){
                    $questao = null;
                }
            }
            $this->questoes[] = $questao;
        }

        //Percorre as questões e monta as alternativas
        foreach ($this->questoes as $indice => $questao) {
            //Cria a resposta correta
            $respostaCorreta = new Resposta();
            $respostaCorreta->id = 0;
            $respostaCorreta->alternativa = $questao->resposta;
            // Insere 4 respostas aleatórias como alternativas
            $this->questoes[$indice]->respostas = $this->questoes[$indice]->respostas->shuffle()->take(4);
            // Insere a resposta Correta como alternativa
            $this->questoes[$indice]->respostas[] = $respostaCorreta; 
            // Emparalha as alternativas
            $this->questoes[$indice]->respostas = $this->questoes[$indice]->respostas->shuffle();
        }

        //Inicializa Placar
        $this->atualizaPlacar();
    }

    public function defineQuestao($questao_id){
        //Define a questão, caso a partida acabe de ser criada pega a primeira do Array questoes
        $questao = $this->questoes->find($questao_id ? $questao_id : $this->questoes[0]->id);
        
        //Pega o indice da questao atual
        $indice = $this->indice = array_search( $questao->id, $this->questoes->pluck('id')->toArray() );

        //Define as questões posterior e anterior
        if($indice > 0 && $indice < $this->questoes->count() -1){
            $this->qAnt = $this->questoes[$indice -1];
            $this->qPost = $this->questoes[$indice +1];
        
        //Última
        }elseif($indice == $this->questoes->count() -1){
            $this->qAnt = $this->questoes[$indice -1];
            $this->qPost = null;

        //Primeira
        }elseif($indice == 0){
            $this->qAnt = null;
            $this->qPost = $this->questoes[$indice +1];

        }
        //Retorna a questão atual
        return $questao;

    }
}
