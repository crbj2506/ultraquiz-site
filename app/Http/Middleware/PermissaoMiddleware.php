<?php

namespace App\Http\Middleware;

use App\Models\PermissaoUser;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;


class PermissaoMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $jogador,$supervisor,$administrador)
    {

        //dd(auth()->user()->permissoes->contains('permissao', '=', 'Administrador'));
        $permissoes = User::find(auth()->user()->id)->permissoes;
        foreach ($permissoes as $key => $permissoaUser) {
            if(
                $permissoaUser->permissao == $jogador ||
                $permissoaUser->permissao == $supervisor ||
                $permissoaUser->permissao == $administrador
            ){
                return $next($request);
            }
        }
        return Response()->view('auth.acesso-negado');
    }
}
