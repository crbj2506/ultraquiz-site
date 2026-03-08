<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\EquipeMultiplayer;
use App\Models\User;

class JogadorPartida extends Model
{
    use HasFactory;

    protected $table = 'jogador_partidas';

    protected $fillable = [
        'partida_multiplayer_id',
        'equipe_multiplayer_id',
        'user_id',
        'is_host',
    ];

    public function equipe()
    {
        return $this->belongsTo(EquipeMultiplayer::class, 'equipe_multiplayer_id');
    }

    public function partida()
    {
        return $this->belongsTo(PartidaMultiplayer::class, 'partida_multiplayer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
