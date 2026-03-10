<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogController extends Controller
{
    protected $log;
    //
    public function __construct(Log $log){
        $this->log = $log;
    }
    public function index(Request $request)
    {
        $logsQuery = $this->log
            ->with('user')
            ->where('tipo', 'AUDIT')
            ->orderByDesc('created_at');

        if ($request->filled('user_id')) {
            $logsQuery->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('metodo')) {
            $metodo = strtoupper((string) $request->input('metodo'));
            $logsQuery->where('dados', 'like', '%"method": "' . $metodo . '"%');
        }

        if ($request->filled('status_code')) {
            $statusCode = (int) $request->input('status_code');
            $logsQuery->where('dados', 'like', '%"status_code": ' . $statusCode . '%');
        }

        if ($request->filled('entidade')) {
            $entidade = strtolower((string) $request->input('entidade'));
            $logsQuery->where('dados', 'like', '%"entity": "' . $entidade . '"%');
        }

        if ($request->filled('rota')) {
            $logsQuery->where('rota', 'like', '%' . $request->input('rota') . '%');
        }

        if ($request->filled('periodo_inicio')) {
            $logsQuery->whereDate('created_at', '>=', $request->input('periodo_inicio'));
        }

        if ($request->filled('periodo_fim')) {
            $logsQuery->whereDate('created_at', '<=', $request->input('periodo_fim'));
        }

        $logs = $logsQuery->paginate(200)->appends($request->query());

        $usuariosAuditaveis = User::whereHas('permissoes', function ($q) {
                $q->whereIn('permissao', ['Supervisor', 'Administrador']);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $entidades = [
            'admin',
            'lobby',
            'permissao',
            'questao',
            'resposta',
            'sugestao',
            'user',
        ];

        return view('log.index', [
            'logs' => $logs,
            'usuariosAuditaveis' => $usuariosAuditaveis,
            'entidades' => $entidades,
            'filtros' => $request->only(['user_id', 'entidade', 'metodo', 'status_code', 'rota', 'periodo_inicio', 'periodo_fim']),
        ]);
    }
}