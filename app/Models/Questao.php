<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Questao extends Model
{
    use HasFactory;
    protected $table = 'questoes';  
    protected $fillable = [
        'pergunta',
        'resposta',
        'fonte'
    ];
    
    public function rules($id){
        return [
            'pergunta' => 'required|unique:questoes,pergunta,'.$id.'|min:10',
            'resposta' => 'required|min:5',
            'fonte' => 'required|min:5',
        ];
    }
    public function feedback(){
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
}
