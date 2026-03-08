<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->setHttpClient(new \GuzzleHttp\Client(['verify' => false]))->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->setHttpClient(new \GuzzleHttp\Client(['verify' => false]))->user();

            $user = User::withTrashed()
                        ->where('email', $googleUser->email)
                        ->orWhere('google_id', $googleUser->id)
                        ->first();

            if ($user) {
                if ($user->trashed()) {
                    $user->restore();
                }
                
                // Atualiza o google_id caso o usuário já existisse (criado via email/senha)
                if (empty($user->google_id)) {
                    $user->update(['google_id' => $googleUser->id]);
                }

                // Se o email ainda não foi verificado, marca como verificado automaticamente na hora do login pelo Google
                if (empty($user->email_verified_at)) {
                    $user->update(['email_verified_at' => now()]);
                }

                // Garante que o perfil tenha ao menos a permissão básica de Jogador
                if ($user->permissoes()->count() === 0) {
                    \App\Models\PermissaoUser::create(['user_id' => $user->id, 'permissao_id' => 1]);
                }
                
                Auth::login($user);
            } else {
                $user = clone User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => Hash::make(Str::random(24)),
                    'google_id' => $googleUser->id,
                    'email_verified_at' => now(), // Assume o email do Google já é verificado
                ]);
                \App\Models\PermissaoUser::create(['user_id' => $user->id, 'permissao_id' => 1]);
                Auth::login($user);
            }

            return redirect()->intended('/partida');
            
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }
}
