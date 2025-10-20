<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\CategoryController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::middleware('jwt.auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', [UserController::class, 'me']);
    Route::apiResource('users', UserController::class);

    Route::get('/recap', [TransactionController::class, 'recap']);

    Route::apiResource('transactions', TransactionController::class);

    Route::get('/categories/option', [CategoryController::class, 'listOption']);
    Route::apiResource('categories', CategoryController::class);
});
