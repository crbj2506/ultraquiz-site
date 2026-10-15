<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\PartidaMultiplayer;
use App\Models\EquipeMultiplayer;
use App\Models\JogadorPartida;
use App\Services\Lobby\CriacaoSalaService;
use App\Services\Lobby\EntradaSalaService;
use App\Services\Lobby\EscolhaEquipeService;
use App\Services\Lobby\InicioPartidaService;
use App\Services\Lobby\LobbyException;
use App\Support\LogMultiplayer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LobbyController extends Controller
{
    public function __construct(
        private CriacaoSalaService $criacaoSala,
        private EntradaSalaService $entradaSala,
        private EscolhaEquipeService $escolhaEquipe,
        private InicioPartidaService $inicioPartida,
    ) {
    }
    public function index()
    {
        // Fluxo de intent: após autenticar, cria sala automaticamente para o anfitrião.
        if (Auth::check() && session()->pull('lobby_auto_create', false)) {
            return $this->create();
        }

        // Tela inicial do Lobby: Exibe formulário para digitar o PIN
        // ou botão para Criar Nova Sala (se for anfitrião)
        return view('lobby.index');
    }

    public function create()
    {
        // Apenas usuários logados podem criar salas
        if (!Auth::check()) {
            session([
                'url.intended' => route('lobby.index'),
                'lobby_auto_create' => true,
            ]);
            return redirect()->route('login')->with('error', 'Você precisa estar logado para criar uma sala.');
        }

        $partida = $this->criacaoSala->criar(Auth::id());
        LogMultiplayer::info('sala_criada', ['pin' => $partida->pin, 'user_id' => Auth::id(), 'ip' => request()->ip()]);

        return redirect()->route('lobby.sala', ['pin' => $partida->pin]);
    }

    public function join(Request $request)
    {
        $request->validate(['pin' => 'required|string|size:4']);
        $pin = strtoupper($request->pin);
        try {
            $this->entradaSala->localizar($pin, 'Ops! Esta partida já foi encerrada e não aceita mais jogadores.');
        } catch (LobbyException $e) {
                        LogMultiplayer::aviso('pin_nao_encontrado', ['pin' => $pin, 'ip' => $request->ip()]);
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('lobby.sala', ['pin' => $pin]);
    }

    public function joinGuest(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:4',
            'nickname' => ['required', 'string', 'max:20', 'regex:/^[\pL\pN][\pL\pN ._-]*$/u'],
        ], [
            'nickname.regex' => 'O apelido deve conter apenas letras, números, espaço, ponto, hífen ou sublinhado.',
        ]);

        $pin = strtoupper($request->pin);

        try {
            $this->entradaSala->localizar($pin, 'Ops! Esta partida já foi encerrada.');
        } catch (LobbyException $e) {
                        LogMultiplayer::aviso('pin_nao_encontrado', ['pin' => $pin, 'ip' => $request->ip()]);
            return redirect()->back()->with('error', $e->getMessage());
        }

        $user = $this->entradaSala->criarConvidado($request->nickname);

        // Loga o convidado e redireciona
        Auth::login($user);
        LogMultiplayer::info('convidado_criado', ['pin' => $pin, 'user_id' => $user->id, 'ip' => $request->ip()]);

        return redirect()->route('lobby.sala', ['pin' => $pin]);
    }

    public function sala($pin)
    {
        if (!Auth::check()) {
            session(['url.intended' => route('lobby.sala', ['pin' => $pin])]);
            return redirect()->route('login')->with('error', 'Você precisa se identificar antes de entrar na sala.');
        }

        $partida = PartidaMultiplayer::with('equipes.jogadores.user')
                    ->where('pin', strtoupper($pin))
                    ->whereIn('status', ['waiting', 'playing'])
                    ->firstOrFail();

        // Se já começou o jogo e o usuário JÁ está em uma equipe, pula direto
        // Se ele não estiver em uma equipe, deve ver a tela do lobby para escolher uma (Late Joiner).
        $jogando = JogadorPartida::whereHas('equipe', function($q) use ($partida) {
            $q->where('partida_multiplayer_id', $partida->id);
        })->where('user_id', Auth::id())->first();

        if ($partida->status === 'playing' && $jogando) {
            return redirect()->route('lobby.jogar', ['pin' => $pin]);
        }

        return view('lobby.sala', [
            'partida' => $partida,
            'jogando' => $jogando,
        ]);
    }
    
    public function escolherEquipe(Request $request, $pin)
    {
        if (!Auth::check()) return response()->json(['error' => 'Not Auth'], 401);

        $request->validate(['equipe_id' => 'required|integer']);

        $partida = PartidaMultiplayer::with('equipes')
            ->where('pin', strtoupper($pin))
            ->whereIn('status', ['waiting', 'playing'])
            ->firstOrFail();

        // Trava a sala: entradas simultâneas veem a mesma contagem de jogadores e não duplicam o jogador.
        [, $mensagemBalanceamento] = DB::transaction(function () use ($partida, $request) {
            PartidaMultiplayer::whereKey($partida->id)->lockForUpdate()->first();
            $equipe = EquipeMultiplayer::where('partida_multiplayer_id', $partida->id)->findOrFail($request->equipe_id);

            return $this->escolhaEquipe->escolher($partida->load('equipes.jogadores'), $equipe->load('jogadores'), Auth::id());
        });

        LogMultiplayer::info('equipe_escolhida', ['pin' => $partida->pin, 'user_id' => Auth::id(), 'equipe_id' => (int) $request->equipe_id]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'mensagem' => $mensagemBalanceamento
            ]);
        }

        if ($mensagemBalanceamento) {
            session()->flash('warning', $mensagemBalanceamento);
        }

        return redirect()->route('lobby.sala', ['pin' => $pin]);
    }

    public function iniciarPartida($pin)
    {
        $partida = PartidaMultiplayer::where('pin', strtoupper($pin))->firstOrFail();

        // Trava a sala: dois cliques simultâneos em "iniciar" não geram duas partidas.
        try {
            DB::transaction(function () use ($pin) {
                $partida = PartidaMultiplayer::where('pin', strtoupper($pin))->lockForUpdate()->firstOrFail();
                $partida->load('equipes');

                $this->inicioPartida->validar($partida, Auth::id());
                $this->inicioPartida->iniciar($partida);
            });
        } catch (LobbyException $e) {
            LogMultiplayer::aviso('inicio_recusado', ['pin' => $partida->pin, 'user_id' => Auth::id(), 'motivo' => $e->getMessage(), 'ip' => request()->ip()]);
            return redirect()->back()->with('error', $e->getMessage());
        }

        LogMultiplayer::info('partida_iniciada', ['pin' => $partida->pin, 'user_id' => Auth::id()]);

        // Se for AJAX, retorna JSON
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true]);
        }

        // O redirecionamento dos outros jogadores será feito via WebSocket no Frontend
        return redirect()->route('lobby.jogar', ['pin' => $pin]);
    }

    public function jogar($pin)
    {
        $partida = PartidaMultiplayer::with('equipes.jogadores.user')
                    ->where('pin', strtoupper($pin))
                    ->where('status', 'playing')
                    ->firstOrFail();

        $minhaEquipe = $partida->equipes->flatMap->jogadores
                            ->where('user_id', Auth::id())
                            ->first()?->equipe;

        return view('lobby.jogar', [
            'partida' => $partida,
            'minhaEquipe' => $minhaEquipe,
            'minhaEquipeId' => $minhaEquipe?->id
        ]);
    }

    public function dadosSala($pin)
    {
        $partida = PartidaMultiplayer::with('equipes.jogadores.user')
                    ->where('pin', strtoupper($pin))
                    ->firstOrFail();

        return response()->json([
            'status' => $partida->status,
            'equipes' => $partida->equipes
        ]);
    }

    public function adminPartidas()
    {
        $partidas = PartidaMultiplayer::with(['user', 'equipes.jogadores'])
            ->whereIn('status', ['waiting', 'playing'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.partidas-ativas', compact('partidas'));
    }
}
