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

    /** Quantidade de questões por partida: override global ou valor do ambiente. */
    public static function quantidadeQuestoes(): int
    {
        $override = config('app.numero_questoes_multiplayer');
        if ($override !== null && $override !== '') {
            return max((int) $override, 0);
        }

        return app()->environment('production')
            ? max((int) config('app.numero_questoes_multiplayer_prod', 10), 0)
            : max((int) config('app.numero_questoes_multiplayer_dev', 3), 0);
    }

    public function equipes()
    {
        return $this->hasMany(EquipeMultiplayer::class);
    }

    public function criador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
