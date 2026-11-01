<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

});

Route::middleware('auth:api')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware('auth:api');
        Route::get('/user', [AuthController::class, 'user']);
        Route::delete('/logout', [AuthController::class, 'logout']);
    });

    Route::apiResource('users', UserController::class);
});
