<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//Route::redirect('/', '/partida');

Auth::routes(['verify' => true, 'register' => true]);

Route::get('/', [App\Http\Controllers\QuestaoController::class, 'principal'])->name('questao.principal');
Route::post('/', [App\Http\Controllers\QuestaoController::class, 'verifica'])->name('questao.verifica');


//Route::middleware('verified', 'permissao:Jogador,Supervisor,Administrador')
//    ->name('home')
//    ->get('/home', [App\Http\Controllers\HomeController::class, 'index']);

Route::middleware('verified', 'permissao:Jogador,Supervisor,Administrador')
    ->name('partida.index')
    ->get('/partida/{questao?}', [App\Http\Controllers\PartidaController::class, 'index']);
Route::middleware('verified', 'permissao:Jogador,Supervisor,Administrador')
    ->name('partida.index')
    ->post('/partida/{questao?}', [App\Http\Controllers\PartidaController::class, 'index']);
    


Route::middleware('verified','permissao:,Supervisor,Administrador')
    ->name('estatistica.index')
    ->get('estatistica', [App\Http\Controllers\QuestaoController::class, 'estatistica']);




###########################
## Funções Supervisor
###########################
Route::middleware('verified', 'permissao:,Supervisor,Administrador')
    ->get('/sugestoes', [App\Http\Controllers\SugestaoController::class, 'listarSugestoes'])->name('sugestoes.listar');
Route::middleware('verified', 'permissao:,Supervisor,Administrador')
    ->get('/sugestoes/{sugestao}', [App\Http\Controllers\SugestaoController::class, 'mostrarSugestao'])->name('sugestao.mostrar');
Route::middleware('verified', 'permissao:,Supervisor,Administrador')
    ->post('/sugestoes/{sugestao}', [App\Http\Controllers\SugestaoController::class, 'aprovarSugestao'])->name('sugestao.aprovar');


Route::middleware('verified', 'permissao:,Supervisor,Administrador')
    ->get('/sugestoesPorMim', [App\Http\Controllers\SugestaoController::class, 'listarSugestoesPorMim'])->name('sugestoespormim.listar');
Route::middleware('verified', 'permissao:,Supervisor,Administrador')
    ->post('/sugestoesPorMim', [App\Http\Controllers\SugestaoController::class, 'armazenarSugestaoPorMim'])->name('sugestaopormim.armazenar');
Route::middleware('verified', 'permissao:,Supervisor,Administrador')
    ->get('/sugestoesPorMim/create', [App\Http\Controllers\SugestaoController::class, 'criarSugestaoPorMim'])->name('sugestaopormim.criar');
Route::middleware('verified', 'permissao:,Supervisor,Administrador')
    ->get('/sugestoesPorMim/{sugestao}', [App\Http\Controllers\SugestaoController::class, 'mostrarSugestaoPorMim'])->name('sugestaopormim.mostrar');
Route::middleware('verified', 'permissao:,Supervisor,Administrador')
    ->get('/sugestoesPorMim/{sugestao}/edit', [App\Http\Controllers\SugestaoController::class, 'editarSugestaoPorMim'])->name('sugestaopormim.editar');
Route::middleware('verified', 'permissao:,Supervisor,Administrador')
    ->put('/sugestoesPorMim/{sugestao}', [App\Http\Controllers\SugestaoController::class, 'atualizarSugestaoPorMim'])->name('sugestaopormim.atualizar');

###########################
## Funções Administrador
###########################
Route::middleware('verified', 'permissao:,,Administrador')
    ->post('/sugestoes', [App\Http\Controllers\SugestaoController::class, 'armazenarSugestao'])->name('sugestao.armazenar');
Route::middleware('verified', 'permissao:,,Administrador')
    ->get('/sugestoes/{sugestao}/edit', [App\Http\Controllers\SugestaoController::class, 'editarSugestao'])->name('sugestao.editar');


Route::middleware('verified', 'permissao:,,Administrador')
    ->resource('sugestao', 'App\Http\Controllers\SugestaoController');

Route::middleware('verified', 'permissao:,,Administrador')
    ->resource('questao', 'App\Http\Controllers\QuestaoController');

Route::middleware('verified', 'permissao:,,Administrador')
    ->resource('permissao', 'App\Http\Controllers\PermissaoController');

Route::middleware('verified', 'permissao:,,Administrador')
    ->resource('user', 'App\Http\Controllers\UserController');

Route::middleware('verified', 'permissao:,,Administrador')
    ->name('resposta.store')
    ->post('resposta', [App\Http\Controllers\RespostaController::class, 'store']);

Route::middleware('verified', 'permissao:,,Administrador')
    ->name('resposta.update')
    ->put('resposta/{resposta}', [App\Http\Controllers\RespostaController::class, 'update']);

Route::middleware('verified', 'permissao:,,Administrador')
    ->name('resposta.edit')
    ->get('resposta/{resposta}/edit', [App\Http\Controllers\RespostaController::class, 'edit']);

Route::middleware('verified','permissao:,,Administrador')
    ->get('/log', [App\Http\Controllers\LogController::class, 'index'])
    ->name('log.index');