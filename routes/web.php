<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])
    ->name('home')
    ->middleware('verified');

Route::resource('questao', 'App\Http\Controllers\QuestaoController')
    ->middleware('verified');

Route::resource('permissao', 'App\Http\Controllers\PermissaoController')
    ->middleware('verified');

Route::resource('user', 'App\Http\Controllers\UserController')
    ->middleware('verified');


Route::post('resposta', [App\Http\Controllers\RespostaController::class, 'store'])
    ->name('resposta.store')
    ->middleware('verified');
Route::put('resposta/{resposta}', [App\Http\Controllers\RespostaController::class, 'update'])
    ->name('resposta.update')
    ->middleware('verified');
Route::get('resposta/{resposta}/edit', [App\Http\Controllers\RespostaController::class, 'edit'])
    ->name('resposta.edit')
    ->middleware('verified');