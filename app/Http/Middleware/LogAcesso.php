<?php

namespace App\Http\Middleware;

use App\Models\Log;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogAcesso
{
    private array $sensitiveKeys = [
        'password',
        'password_confirmation',
        '_token',
        'token',
        'access_token',
        'refresh_token',
        'client_secret',
        'authorization',
        'cookie',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $user = Auth::user();
        if (!$user || !($user instanceof \App\Models\User) || !empty($user->is_guest)) {
            return $response;
        }

        $isPrivilegiado = $user->permissoes()->whereIn('permissao', ['Supervisor', 'Administrador'])->exists();
        if (!$isPrivilegiado) {
            return $response;
        }

        $metodo = strtoupper($request->method());
        $deveAuditar = in_array($metodo, ['POST', 'PUT', 'PATCH', 'DELETE'], true);

        if (!$deveAuditar) {
            return $response;
        }

        $dados = [
            'audit' => true,
            'entity' => $this->inferEntity($request),
            'method' => $metodo,
            'route_name' => optional($request->route())->getName(),
            'status_code' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
            'user_agent' => (string) $request->userAgent(),
            'request' => $this->sanitizeArray($request->all()),
        ];

        try {
            Log::create([
                'tipo' => 'AUDIT',
                'ip_origem' => $request->ip(),
                'rota' => $request->getRequestUri(),
                'user_id' => $user->id,
                'dados' => json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable $e) {
            // Evita quebrar o fluxo principal por falha de auditoria.
        }

        return $response;
    }

    private function sanitizeArray(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), $this->sensitiveKeys, true)) {
                $payload[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->sanitizeArray($value);
            }
        }

        return $payload;
    }

    private function inferEntity(Request $request): string
    {
        $routeName = (string) optional($request->route())->getName();
        if ($routeName !== '') {
            $base = strtolower(explode('.', $routeName)[0]);
            return $base !== '' ? $base : 'sistema';
        }

        $segmento = strtolower((string) $request->segment(1));
        return $segmento !== '' ? $segmento : 'sistema';
    }
}
