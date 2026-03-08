<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Added this line
use Illuminate\Database\Eloquent\Model;

class PartidaMultiplayer extends Model
{
    use HasFactory;

    protected $table = 'partida_multiplayers';

    protected $fillable = [
        'pin',
        'status',
        'user_id',
        'questoes_json',
        'pergunta_atual_index',
    ];

    public function equipes()
    {
        return $this->hasMany(EquipeMultiplayer::class);
    }

    public function criador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
