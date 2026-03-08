<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VotoQuestao extends Model
{
    protected $table = 'voto_questaos';

    protected $fillable = [
        'questao_id',
        'user_id',
        'voto',
    ];

    public function questao()
    {
        return $this->belongsTo(Questao::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
