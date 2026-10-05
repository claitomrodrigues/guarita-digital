<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AcessoController;
use App\Http\Controllers\CapturaCameraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PessoaController;
use App\Http\Controllers\TriagemController;
use App\Http\Controllers\VeiculoController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'home' : 'login'));

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware(['auth', 'ativo'])->group(function (): void {
    Route::get('/home', DashboardController::class)->name('home');
    Route::post('/camera/reconhecer', CapturaCameraController::class)->name('camera.reconhecer');
    Route::post('/acessos/manuais', [AcessoController::class, 'registrarManual'])->name('acessos.registrar-manual');
    Route::put('/triagens/{triagem}/concluir', [TriagemController::class, 'concluir'])->name('triagens.concluir');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::resource('condutores', PessoaController::class)
        ->parameters(['condutores' => 'pessoa'])
        ->except('show');

    Route::resource('veiculos', VeiculoController::class)->except('show');
});
