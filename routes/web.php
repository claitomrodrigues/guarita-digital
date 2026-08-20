<?php

use App\Http\Controllers\AcessoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConfiguracaoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LogAuditoriaController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\PessoaController;
use App\Http\Controllers\PontoAcessoController;
use App\Http\Controllers\ReconhecimentoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VeiculoController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'sistema' => 'Guarita Digital',
    'status' => 'online',
    'api' => url('/api'),
]));

Route::prefix('api')->group(function (): void {
    Route::get('/', fn () => response()->json([
        'sistema' => 'Guarita Digital',
        'versao_backend' => '2.0.0',
        'autenticacao' => 'sessao',
        'perfis' => ['administrador', 'seguranca'],
    ]));

    Route::get('/health', HealthController::class)->name('health');
    Route::get('/csrf-token', fn () => response()->json(['token' => csrf_token()]));
    Route::get('/configuracoes-publicas', [ConfiguracaoController::class, 'publicas']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware(['auth', 'ativo'])->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/meta', MetaController::class);
        Route::get('/dashboard', DashboardController::class);

        Route::get('/pessoas', [PessoaController::class, 'index']);
        Route::get('/pessoas/{pessoa}', [PessoaController::class, 'show']);

        Route::get('/veiculos', [VeiculoController::class, 'index']);
        Route::get('/veiculos/{veiculo}', [VeiculoController::class, 'show']);

        Route::get('/acessos', [AcessoController::class, 'index']);
        Route::get('/acessos/{acesso}', [AcessoController::class, 'show']);
        Route::get('/acessos/{acesso}/imagem', [AcessoController::class, 'imagem'])
            ->name('acessos.imagem');

        Route::get('/pontos-acesso', [PontoAcessoController::class, 'index']);
        Route::get('/pontos-acesso/{ponto_acesso}', [PontoAcessoController::class, 'show']);

        Route::middleware('perfil:administrador,seguranca')->group(function (): void {
            Route::post('/acessos/manual', [AcessoController::class, 'registrarManual']);
            Route::patch('/acessos/{acesso}/liberar', [AcessoController::class, 'liberar']);
            Route::post('/reconhecimento/placa', [ReconhecimentoController::class, 'reconhecer']);
        });

        Route::middleware('perfil:administrador')->group(function (): void {
            Route::post('/pessoas', [PessoaController::class, 'store']);
            Route::match(['put', 'patch'], '/pessoas/{pessoa}', [PessoaController::class, 'update']);
            Route::delete('/pessoas/{pessoa}', [PessoaController::class, 'destroy']);

            Route::post('/veiculos', [VeiculoController::class, 'store']);
            Route::match(['put', 'patch'], '/veiculos/{veiculo}', [VeiculoController::class, 'update']);
            Route::delete('/veiculos/{veiculo}', [VeiculoController::class, 'destroy']);

            Route::get('/usuarios', [UserController::class, 'index']);
            Route::get('/usuarios/{usuario}', [UserController::class, 'show']);
            Route::match(['put', 'patch'], '/usuarios/{usuario}', [UserController::class, 'update']);

            Route::post('/pontos-acesso', [PontoAcessoController::class, 'store']);
            Route::match(['put', 'patch'], '/pontos-acesso/{ponto_acesso}', [PontoAcessoController::class, 'update']);
            Route::delete('/pontos-acesso/{ponto_acesso}', [PontoAcessoController::class, 'destroy']);

            Route::get('/configuracoes', [ConfiguracaoController::class, 'index']);
            Route::patch('/configuracoes/{configuracao}', [ConfiguracaoController::class, 'update']);
            Route::get('/auditoria', [LogAuditoriaController::class, 'index']);
        });
    });
});
