<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PartidaMultiplayer;
use App\Models\JogadorPartida;

class EquipeMultiplayer extends Model
{
    use HasFactory;

    protected $table = 'equipe_multiplayers';

    protected $fillable = [
        'partida_multiplayer_id',
        'nome',
        'cor',
        'pontuacao',
    ];

    public function partida()
    {
        return $this->belongsTo(PartidaMultiplayer::class, 'partida_multiplayer_id');
    }

    public function jogadores()
    {
        return $this->hasMany(JogadorPartida::class);
    }
}
