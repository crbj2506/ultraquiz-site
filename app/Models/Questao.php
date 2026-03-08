<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Questao extends Model
{
    use HasFactory;
    protected $table = 'questoes';  
    protected $fillable = [
        'pergunta',
        'resposta',
        'fonte',
        'user_id',
    ];
    
    public function rules($id){
        return [
            'pergunta' => 'required|unique:questoes,pergunta,'.$id.'|min:10',
            'resposta' => 'required|min:1',
            'fonte' => 'required|min:5',
        ];
    }
    public static function rulesSugestao($sugestao){
        return [
            'pergunta' => 'required|unique:questoes,pergunta,'.$sugestao.'|min:10',
            'resposta' => 'required|min:1',
            'fonte' => 'required|min:5',
            'alternativa.*' => 'required|min:1',
            'alternativas.*' => 'required|min:1'
        ];
    }
    public static function feedback(){
        return [
            'required' => 'O campo :attribute é obrigatório',
            'pergunta.unique' => 'A pergunta já existe em nosso Quiz',
            'pergunta.min' => 'O campo :attribute deve ter no mínimo 10 caracteres',
            'resposta.min' => 'O campo :attribute deve ter no mínimo 5 caracteres',
            'fonte.min' => 'O campo :attribute deve ter no mínimo 5 caracteres',
        ];
    }
    public function respostas(){
        return $this->hasMany('App\Models\Resposta');
    }
    public function estatisticas(){
        return $this->hasMany('App\Models\Estatistica');
    }
    public function votos(){
        return $this->hasMany('App\Models\VotoQuestao');
    }

    public function user(){
        return $this->belongsTo('App\Models\User');
        
    }
    public function verificacoes(){
        $collection = $this->belongsToMany('App\Models\User','verificacoes')->withPivot('aprovada');
        return $collection;
    }
    public function verifica(){

        // Verifica se o criador da questão é Administrador através do relacionamento de permissões
        $user = $this->user;
        $verificada = $user && $user->permissoes()->where('permissao', 'Administrador')->exists();

        // Conta a quantidade de Aprovações antigas (sistema interno)
        $aprovadas = $this->verificacoes()->where('aprovada','=','1')->count();
        $reprovadas = $this->verificacoes()->where('aprovada','=','0')->count();

        // Novo Sistema de Curadoria Colaborativa:
        // Soma os votos (+1 ou -1) para ver se a comunidade já validou a questão
        $scoreComunidade = $this->votos()->sum('voto');

        // Verificar se tem no mínimo 4 alternativas
        $alternativas = $this->respostas()->count() > 3 ? true : false;

        // É aprovada se: é de um admin, ou tem mais aprovações manuais que reprovações, ou alcançou +10 na curadoria
        if (($verificada || $aprovadas > $reprovadas || $scoreComunidade >= 10) && $alternativas) {
            return true;
        } else {
            return false;
        }
    }

    public static function aleatoria(){
        $limit = max((int)(Questao::count() * 0.75), 1);
        $recentes = Estatistica::orderBy('id', 'desc')->limit($limit)->pluck('questao_id')->toArray();
        $questao = static::with(['respostas', 'user.permissoes', 'verificacoes'])
            ->whereNotIn('questoes.id', $recentes);
        return $questao;
    }

    public static function facil(){
        $limit = max((int)(Questao::count() * 0.75), 1);
        $recentes = Estatistica::orderBy('id', 'desc')->limit($limit)->pluck('questao_id')->toArray();
        $questao = static::with(['respostas', 'user.permissoes', 'verificacoes'])
            ->select('*')
            ->selectRaw('
                (SELECT count(*) as respondida FROM questoes q join estatisticas e on e.questao_id = q.id where q.id = questoes.id and e.resposta_id is null)
                / 
                GREATEST((SELECT count(*) as respondida FROM questoes q join estatisticas e on e.questao_id = q.id where q.id = questoes.id), 1) as taxa_acerto, 
                (SELECT count(*) as respondida FROM questoes q join estatisticas e on e.questao_id = q.id where q.id = questoes.id) as respondida
            ')
            ->whereNotIn('questoes.id', $recentes)
            ->having('respondida','>','2')
            ->orderByDesc('taxa_acerto')
            ->orderByDesc('respondida');
        return $questao;
    }

    public function taxaAcerto(){
        $vezesAcerto = $this->estatisticas->where('resposta_id', null)->count();
        $vezesRespondida = $this->vezesRespondida();
        if($vezesRespondida > 0){ //Evita divisão por 0 (zero)
            return $vezesAcerto / $vezesRespondida * 100;
        }else{
            return 0;
        }
    }
    public function vezesRespondida(){
        return $this->estatisticas->pluck('resposta_id')->count();
    }
    public function aprovacoes(){
        return $this->verificacoes() ? $this->verificacoes()->where('aprovada', 1)->get()->count() : 0;
    }
    public function reprovacoes(){
        return $this->verificacoes() ? $this->verificacoes()->where('aprovada', 0)->get()->count() : 0;
    }
    public function verifiquei(){

        if ($this->verificacoes()->where('user_id', Auth::id())->get()->isEmpty()) {
            return null;
        } else {
            return $this->verificacoes()->where('user_id', Auth::id())->first()->pivot->aprovada;
        }

    }
}
