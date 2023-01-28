<?php

use Illuminate\Support\Facades\Route;
//use App\Http\Middleware\PermissaoMiddleware;

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

Route::get('/', [App\Http\Controllers\QuestaoController::class, 'principal'])
    ->name('questao.principal');
Route::post('/', [App\Http\Controllers\QuestaoController::class, 'verifica'])
    ->name('questao.verifica');

Auth::routes(['verify' => true, 'register' => false]);

Route::middleware('verified', 'permissao:Jogador,Supervisor,Administrador')
    ->name('home')
    ->get('/home', [App\Http\Controllers\HomeController::class, 'index']);

Route::name('partida.index')
    ->get('/partida/{questao?}', [App\Http\Controllers\PartidaController::class, 'index']);
Route::name('partida.index')
    ->post('/partida/{questao?}', [App\Http\Controllers\PartidaController::class, 'index']);
    
Route::middleware('verified', 'permissao:,Supervisor,Administrador')
    ->resource('questao', 'App\Http\Controllers\QuestaoController');

Route::middleware('verified', 'permissao:,,Administrador')
    ->resource('permissao', 'App\Http\Controllers\PermissaoController');

Route::middleware('verified', 'permissao:,,Administrador')
    ->resource('user', 'App\Http\Controllers\UserController');

Route::middleware('verified', 'permissao:,Supervisor,Administrador')
    ->name('resposta.store')
    ->post('resposta', [App\Http\Controllers\RespostaController::class, 'store']);
Route::middleware('verified', 'permissao:,Supervisor,Administrador')
    ->name('resposta.update')
    ->put('resposta/{resposta}', [App\Http\Controllers\RespostaController::class, 'update']);
Route::middleware('verified', 'permissao:,Supervisor,Administrador')
    ->name('resposta.edit')
    ->get('resposta/{resposta}/edit', [App\Http\Controllers\RespostaController::class, 'edit']);

Route::middleware('verified','permissao:,Supervisor,Administrador')
    ->name('estatistica.index')
    ->get('estatistica', [App\Http\Controllers\QuestaoController::class, 'estatistica']);

Route::middleware('verified','permissao:,,Administrador')
    ->get('/log', [App\Http\Controllers\LogController::class, 'index'])
    ->name('log.index');