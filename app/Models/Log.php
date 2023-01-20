<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;
    protected $fillable = [
        'tipo',
        'ip_origem',
        'user_id',
        'rota',
        'dados',
    ];

    public function user(){
        return $this->belongsTo('App\Models\User');
    }
}