<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'is_guest',
        'email_verified_at',
        'experiencia',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function rules($id){
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$id],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            //'permissao' => 'required|unique:permissoes,permissao,'.$id.'|min:3'
        ];
    }

    public function rules_update($id){
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$id],
            //'permissao' => 'required|unique:permissoes,permissao,'.$id.'|min:3'
        ];
    }
    public function feedback(){
        return [
            'required' => 'O campo :attribute é obrigatório',
        ];
    }

    public function permissoes(){
        return $this->belongsToMany('App\Models\Permissao', 'permissoes_users');
    }

    public function getTituloAttribute()
    {
        $xp = $this->experiencia;
        if ($xp < 50) return 'Estudante';
        if ($xp < 150) return 'Publicador';
        if ($xp < 300) return 'Pioneiro';
        if ($xp < 500) return 'Servo Ministerial';
        if ($xp < 800) return 'Ancião';
        if ($xp < 1200) return 'Superintendente';
        if ($xp < 2000) return 'Betelita';
        return 'Ungido';
    }

    public function adicionarExperienciaBaseadoNaQuestao(\App\Models\Questao $questao, bool $acertou)
    {
        $totalRespostas = $questao->acertos + $questao->erros;
        
        $deltaXp = 0;

        if ($totalRespostas < 10) {
            $deltaXp = $acertou ? 2 : 0;
        } else {
            $taxaAcerto = ($questao->acertos / $totalRespostas) * 100;

            if ($taxaAcerto > 70) {
                // Fácil
                $deltaXp = $acertou ? 1 : -3;
            } elseif ($taxaAcerto >= 40) {
                // Média
                $deltaXp = $acertou ? 3 : -1;
            } else {
                // Difícil
                $deltaXp = $acertou ? 5 : 0;
            }
        }

        $this->experiencia += $deltaXp;
        if ($this->experiencia < 0) {
            $this->experiencia = 0;
        }
        
        $this->save();

        return $deltaXp;
    }
}
