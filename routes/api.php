<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [UserController::class, 'user']);
    Route::post('/user', [UserController::class, 'update']);
    
    // Ticket routes
    Route::prefix('ticket')->group(function(){
        Route::post('/', [TicketController::class, 'store']);
        Route::get('/', [TicketController::class, 'index']);
        Route::get('/history', [TicketController::class, 'history']);
        Route::get('/favorites', [TicketController::class, 'favorites']);
        Route::get('/{ticket}', [TicketController::class, 'show']);
        Route::post('/{ticket}/favorite', [TicketController::class, 'toggleFavorite']);
    });
});
