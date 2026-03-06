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
        $this->questoes = new Collection();
        $num_questoes = max((int) config('app.numero_questoes_partida', 20), 0);

        if ($num_questoes <= 0) {
            $this->atualizaPlacar();
            return;
        }

        $idQuestoesPartida = [];

        // 1. Traz as fáceis em um único lote (traz a mais para poder sortear 5)
        $faceis = Questao::facil()->limit(15)->get()->filter(function($q) {
            return $q->verifica();
        })->shuffle();

        $qtdFaceis = min(5, $num_questoes);
        foreach ($faceis->take($qtdFaceis) as $q) {
            $idQuestoesPartida[] = $q->id;
            $this->questoes->push($q);
        }

        // 2. Completa o restante com aleatórias usando um único lote
        $falta = $num_questoes - count($idQuestoesPartida);
        if ($falta > 0) {
            $aleatorias = Questao::aleatoria()->limit($falta * 3)->get()->filter(function($q) use ($idQuestoesPartida) {
                return !in_array($q->id, $idQuestoesPartida) && $q->verifica();
            })->shuffle();

            foreach ($aleatorias->take($falta) as $q) {
                $idQuestoesPartida[] = $q->id;
                $this->questoes->push($q);
            }
        }

        //Percorre as questões e monta as alternativas
        foreach ($this->questoes as $indice => $questao) {
            //Cria a resposta correta
            $respostaCorreta = new Resposta();
            $respostaCorreta->id = 0;
            $respostaCorreta->alternativa = $questao->resposta;
            // Insere 4 respostas aleatórias como alternativas
            $questao->respostas = $questao->respostas->shuffle()->take(4);
            // Insere a resposta Correta como alternativa
            $questao->respostas->push($respostaCorreta); 
            // Emparalha as alternativas
            $questao->respostas = $questao->respostas->shuffle();
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
