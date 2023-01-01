<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estatistica extends Model
{
    use HasFactory;
    protected $fillable = [
        'questao_id',
        'resposta_id',
    ];
    public function questao(){
        return $this->belongsTo('App\Models\Questao');
    } 
    public function resposta(){
        return $this->belongsTo('App\Models\Resposta');
    } 
}
