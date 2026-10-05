<?php

use App\Http\Controllers\CameraApiController;
use App\Http\Controllers\TriagemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->patch('/triagens/{triagem}/concluir', [TriagemController::class, 'concluir']);

Route::prefix('v1/camera')
    ->middleware(['camera', 'throttle:120,1'])
    ->group(function (): void {
        Route::post('/reconhecimentos', [CameraApiController::class, 'reconhecer']);
        Route::post('/heartbeat', [CameraApiController::class, 'heartbeat']);
    });
