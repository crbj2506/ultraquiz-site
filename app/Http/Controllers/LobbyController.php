<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\PartidaMultiplayer;
use App\Models\EquipeMultiplayer;
use App\Models\JogadorPartida;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class LobbyController extends Controller
{
    public function index()
    {
        // Tela inicial do Lobby: Exibe formulário para digitar o PIN
        // ou botão para Criar Nova Sala (se for anfitrião)
        return view('lobby.index');
    }

    public function create()
    {
        // Apenas usuários logados podem criar salas
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Você precisa estar logado para criar uma sala.');
        }

        // Gera um PIN alfanumérico único de 4 caracteres
        do {
            $pin = strtoupper(Str::random(4));
        } while (PartidaMultiplayer::where('pin', $pin)->where('status', 'waiting')->exists());

        // Cria a partida no banco
        $partida = PartidaMultiplayer::create([
            'pin' => $pin,
            'status' => 'waiting', // waiting, playing, finished
            'user_id' => Auth::id()
        ]);

        // Cria as duas equipes padrão
        $equipeA = EquipeMultiplayer::create(['partida_multiplayer_id' => $partida->id, 'nome' => 'Equipe A', 'cor' => 'primary']);
        $equipeB = EquipeMultiplayer::create(['partida_multiplayer_id' => $partida->id, 'nome' => 'Equipe B', 'cor' => 'danger']);

        return redirect()->route('lobby.sala', ['pin' => $pin]);
    }

    public function join(Request $request)
    {
        $request->validate(['pin' => 'required|string|size:4']);
        $pin = strtoupper($request->pin);
        
        $partida = PartidaMultiplayer::where('pin', $pin)->first();
        if (!$partida) {
            return redirect()->back()->with('error', 'Sala não encontrada. Verifique o PIN e tente novamente.');
        }
        if ($partida->status === 'finished') {
            return redirect()->back()->with('error', 'Ops! Esta partida já foi encerrada e não aceita mais jogadores.');
        }

        return redirect()->route('lobby.sala', ['pin' => $pin]);
    }

    public function joinGuest(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:4',
            'nickname' => 'required|string|max:20'
        ]);

        $pin = strtoupper($request->pin);
        
        // Verifica se a sala existe
        $partida = PartidaMultiplayer::where('pin', $pin)->first();
        if (!$partida) {
            return redirect()->back()->with('error', 'Sala não encontrada. Verifique o PIN e tente novamente.');
        }
        if ($partida->status === 'finished') {
            return redirect()->back()->with('error', 'Ops! Esta partida já foi encerrada.');
        }

        // Cria um usuário convidado temporário
        $user = \App\Models\User::create([
            'name' => $request->nickname . ' (Convidado)',
            'email' => 'guest_' . uniqid() . '@ultraquiz.com',
            'password' => \Illuminate\Support\Facades\Hash::make(Str::random(16)),
            'is_guest' => true,
            'email_verified_at' => now(),
        ]);

        // Loga o convidado e redireciona
        \Illuminate\Support\Facades\Auth::login($user);

        return redirect()->route('lobby.sala', ['pin' => $pin]);
    }

    public function sala($pin)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Você precisa se identificar antes de entrar na sala.');
        }

        $partida = PartidaMultiplayer::with('equipes.jogadores.user')
                    ->where('pin', strtoupper($pin))
                    ->whereIn('status', ['waiting', 'playing'])
                    ->firstOrFail();

        // Se já começou, pula direto para o jogo
        if ($partida->status === 'playing') {
            return redirect()->route('lobby.jogar', ['pin' => $pin]);
        }

        // O usuário já está em alguma equipe desta partida?
        $jogando = JogadorPartida::whereHas('equipe', function($q) use ($partida) {
            $q->where('partida_multiplayer_id', $partida->id);
        })->where('user_id', Auth::id())->first();

        return view('lobby.sala', [
            'partida' => $partida,
            'jogando' => $jogando,
        ]);
    }
    
    public function escolherEquipe(Request $request, $pin)
    {
        if (!Auth::check()) return response()->json(['error' => 'Not Auth'], 401);

        $partida = PartidaMultiplayer::where('pin', strtoupper($pin))->firstOrFail();
        $equipe_id = $request->equipe_id;

        // Verifica se a equipe pertence a esta partida
        $equipe = EquipeMultiplayer::where('partida_multiplayer_id', $partida->id)->findOrFail($equipe_id);

        // Remove o jogador de outras equipes desta mesma partida (se houver)
        $jogadoresAtuais = JogadorPartida::where('partida_multiplayer_id', $partida->id)
                                         ->where('user_id', Auth::id())
                                         ->get();
        
        foreach($jogadoresAtuais as $j) {
            $j->delete();
        }

        // Adiciona à nova equipe
        JogadorPartida::create([
            'partida_multiplayer_id' => $partida->id,
            'equipe_multiplayer_id' => $equipe->id,
            'user_id' => Auth::id()
        ]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('lobby.sala', ['pin' => $pin]);
    }

    public function iniciarPartida($pin)
    {
        $partida = PartidaMultiplayer::where('pin', strtoupper($pin))->firstOrFail();

        // Apenas o criador pode iniciar
        if (Auth::id() !== $partida->user_id) {
            return redirect()->back()->with('error', 'Apenas o anfitrião pode iniciar a partida.');
        }

        // Validação: Pelo menos 1 jogador em CADA equipe principal
        $equipeA = $partida->equipes->where('nome', 'Equipe A')->first();
        $equipeB = $partida->equipes->where('nome', 'Equipe B')->first();
        
        $countA = $equipeA ? $equipeA->jogadores()->count() : 0;
        $countB = $equipeB ? $equipeB->jogadores()->count() : 0;

        if ($countA < 1 || $countB < 1) {
            return redirect()->back()->with('error', 'Ambas as equipes precisam de pelo menos 1 jogador para começar.');
        }

        // Zera pontuações de partidas anteriores se houver (Rematch safety)
        foreach ($partida->equipes as $eq) {
            $eq->update(['pontuacao' => 0]);
        }

        // Gera as questões da partida usando a lógica existente do Solo
        $p = new \App\Models\Partida();
        $p->criar();
        $questoesData = $p->getState()['questoes_data'];
        
        // Temporariamente reduz para 3 questões conforme pedido
        if (count($questoesData) > 3) {
            $questoesData = array_slice($questoesData, 0, 3);
        }

        $partida->update([
            'status' => 'playing',
            'questoes_json' => json_encode($questoesData),
            'pergunta_atual_index' => 0
        ]);

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
}
