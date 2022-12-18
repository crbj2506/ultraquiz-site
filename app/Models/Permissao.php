<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permissao extends Model
{
    use HasFactory;
    protected $table = 'permissoes';  
    protected $fillable = [
        'permissao'
    ];
    public function rules($id){
        return [
            'permissao' => 'required|unique:permissoes,permissao,'.$id.'|min:3'
        ];
    }
    public function feedback(){
        return [
            'required' => 'O campo :attribute é obrigatório',
            'permissao.min' => 'O campo :attribute deve ter no mínimo 3 caracteres'
        ];
    }
}
