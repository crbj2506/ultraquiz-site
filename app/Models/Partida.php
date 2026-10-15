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
        //Atualiza Placar baseando-se nas respostas já dadas
        $this->a = $this->b = $this->e = 0;
        foreach ($this->questoes as $q) {
            if ($q->respAnt === '0') {
                $this->a++;
            } elseif ($q->respAnt === null) {
                $this->b++;
            } else {
                $this->e++;
            }
        }
    }

    public function getState()
    {
        // Exporta de forma super leve para a Sessão (Apenas IDs e Inteiros)
        $questoesData = [];
        foreach ($this->questoes as $q) {
            $questoesData[] = [
                'id' => $q->id,
                'respAnt' => $q->respAnt,
                'opcoes' => $q->respostas->pluck('id')->toArray()
            ];
        }

        return [
            'questoes_data' => $questoesData,
            'indice' => $this->indice ?? 0,
            'a' => $this->a,
            'b' => $this->b,
            'e' => $this->e
        ];
    }

    public function restoreState($state)
    {
        // Reconstrói a Partida puxando dados base das questões em lote
        $this->a = $state['a'];
        $this->b = $state['b'];
        $this->e = $state['e'];
        $this->indice = $state['indice'];
        
        $idsQuestoes = collect($state['questoes_data'])->pluck('id')->toArray();
        $todasRespostasIds = collect($state['questoes_data'])->pluck('opcoes')->flatten()->unique()->filter(function($id) { return $id !== 0; })->toArray();
        
        // Puxa as questões e respostas ativas do banco em apenas 2 queries master
        $bancoQuestoes = Questao::whereIn('id', $idsQuestoes)->get()->keyBy('id');
        $bancoRespostas = Resposta::whereIn('id', $todasRespostasIds)->get()->keyBy('id');
        
        $this->questoes = new Collection();
        
        foreach ($state['questoes_data'] as $qData) {
            if (!isset($bancoQuestoes[$qData['id']])) continue;
            
            $q = clone $bancoQuestoes[$qData['id']];
            $q->respAnt = $qData['respAnt'];
            $q->respostas = new Collection();
            
            // Remonta as opções respeitando a ordem original que foi embaralhada pelo criar()
            foreach ($qData['opcoes'] as $respId) {
                if ($respId === 0) {
                    $respostaCorreta = new Resposta();
                    $respostaCorreta->id = 0;
                    $respostaCorreta->alternativa = $q->resposta;
                    $q->respostas->push($respostaCorreta);
                } elseif (isset($bancoRespostas[$respId])) {
                    $q->respostas->push($bancoRespostas[$respId]);
                }
            }
            
            $this->questoes->push($q);
        }
    }
    /** Divide o total em 25% fáceis, 25% difíceis e o restante médias (20 = 5/10/5). */
    public static function distribuicaoPorDificuldade(int $total): array
    {
        $facil = (int) round($total * 0.25);
        $dificil = (int) round($total * 0.25);

        return ['facil' => $facil, 'media' => $total - $facil - $dificil, 'dificil' => $dificil];
    }

    /** Escolhe até $quantidade questões verificadas da consulta, sem repetir as já escolhidas. */
    private function sortear($consulta, int $quantidade, array &$ids): array
    {
        if ($quantidade <= 0) {
            return [];
        }

        $escolhidas = [];
        $candidatas = $consulta->limit($quantidade * 3)->get()
            ->filter(fn ($q) => !in_array($q->id, $ids) && $q->verifica());

        foreach ($candidatas->take($quantidade) as $q) {
            $ids[] = $q->id;
            $escolhidas[] = $q;
        }

        return $escolhidas;
    }

    /**
     * Monta a partida em ordem crescente de dificuldade: fáceis, médias e difíceis.
     * Faltando questões numa faixa, completa com aleatórias.
     */
    public function criar(?int $quantidade = null){
        $this->questoes = new Collection();
        if ($quantidade !== null) {
            $num_questoes = max($quantidade, 0);
        } else {
            $override = config('app.numero_questoes_partida');
            if ($override !== null && $override !== '') {
                $num_questoes = max((int) $override, 0);
            } else {
                $num_questoes = app()->environment('production')
                    ? max((int) config('app.numero_questoes_partida_prod', 20), 0)
                    : max((int) config('app.numero_questoes_partida_dev', 5), 0);
            }
        }

        if ($num_questoes <= 0) {
            $this->atualizaPlacar();
            return;
        }

        $ids = [];
        $quota = self::distribuicaoPorDificuldade($num_questoes);

        // Curadoria Colaborativa: 1 questão pendente para a comunidade avaliar (sem estatística, conta como média)
        $candidatasMistas = Questao::with(['respostas', 'user.permissoes', 'verificacoes', 'votos'])
            ->inRandomOrder()->limit(40)->get();

        $pendentes = $candidatasMistas->filter(function($q) {
            return \str_word_count($q->pergunta) > 2 // Verificação extra de sanidade
                   && !$q->verifica() 
                   && $q->votos()->sum('voto') >= -3 
                   && $q->respostas()->count() >= 4;
        })->shuffle();

        $pendente = [];
        if ($quota['media'] > 0 && $pendentes->isNotEmpty()) {
            $pendente = [$pendentes->first()];
            $ids[] = $pendente[0]->id;
            $quota['media']--;
        }

        $faceis = $this->sortear(Questao::facil(), $quota['facil'], $ids);
        $medias = array_merge($pendente, $this->sortear(Questao::media(), $quota['media'], $ids));
        $dificeis = $this->sortear(Questao::dificil(), $quota['dificil'], $ids);

        foreach (array_merge($faceis, $medias, $dificeis) as $q) {
            $this->questoes->push($q);
        }

        // Completa o que faltou (faixa sem questões suficientes) com aleatórias
        $falta = $num_questoes - $this->questoes->count();
        foreach ($this->sortear(Questao::aleatoria(), $falta, $ids) as $q) {
            $this->questoes->push($q);
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
