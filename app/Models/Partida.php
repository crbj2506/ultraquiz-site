<?php

namespace App\Models;

use App\Models\Questao;
use App\Models\Resposta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Partida extends Model
{
    use HasFactory;

    public function criar(){

        //Busca Questões aleatórias
        $this->questoes = Questao::all()->shuffle()->take(env('APP_NUMERO_QUESTOES_PARTIDA'));

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
