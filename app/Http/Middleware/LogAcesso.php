<?php

namespace App\Http\Middleware;

use App\Models\Log;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogAcesso
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        //dd($request, $request->getRequestUri());
        $dados = null;
        if(!empty($request->method())){
            $dados['method'] = $request->method();
        }
        if(!empty($request->all())){
            $dados['request'] = $request->all();
        }
        Log::create([
            'tipo' => 'REQUEST',
            'ip_origem' => $request->ip(),
            'rota' => $request->getRequestUri(),
            'user_id' => Auth::user() ? Auth::user()->id : null,
            'dados' => json_encode($dados,JSON_PRETTY_PRINT)
        ]);
        return $next($request);
    }
}
