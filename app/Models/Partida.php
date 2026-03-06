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
    public function criar(){ // MELHORAR // Verificar se tem questões suficientes para a partida antes do loop ou fazer escape com a informação
        $this->questoes = new Collection();

        $idQuestoesPartida = [];
        // Sorteia as Questões da Partida
        for ($i = 0; $i < max((int) config('app.numero_questoes_partida', 20), 0); $i++) {

            // Para as cinco primeiras Questões da partida
            if ($i < 5) {
                // Sorteia uma Questão das 10 mais fáceis (dentro da função), não recentes (dentro da função), que ainda não estejam na partida
                $questao = Questao::facil()->whereNotIn('id', $idQuestoesPartida)->get()->shuffle()->first();

            // Para as demais posições 
            } else {
                $questao = Questao::aleatoria()->whereNotIn('id', $idQuestoesPartida)->get()->shuffle()->first();
            }

            // Verifica Questão ( Se é de um Administraddor, se tem alternativas suficientes e se está aprovada)
            if ($questao && $questao->verifica()) {
                $idQuestoesPartida[] = $questao->id;
                $this->questoes[] = $questao;
            } else {
                $i--;
            }
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
        if ($this->questoes->isEmpty()) {
            $this->indice = 0;
            $this->qAnt = null;
            $this->qPost = null;

            return null;
        }

        //Define a questão, caso a partida acabe de ser criada pega a primeira do Array questoes
        $questao = $questao_id ? $this->questoes->find($questao_id) : $this->questoes->first();

        if (! $questao) {
            $questao = $this->questoes->first();
        }
        
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
            $this->qPost = $this->questoes[$indice +1] ?? null;

        }

        //Retorna a questão atual
        return $questao;

    }
}
