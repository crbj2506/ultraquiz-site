<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resposta extends Model
{
    use HasFactory;
    protected $fillable = [
        'questao_id',
        'alternativa',
    ];
    
    public function rules($id){
        return [
            'alternativa' => 'required|min:1',
        ];
    }
    public function feedback(){
        return [
            'required' => 'O campo :attribute é obrigatório',
        ];
    }
    public function pergunta(){
        return $this->belongsTo('App\Models\Pergunta');
    } 
}
