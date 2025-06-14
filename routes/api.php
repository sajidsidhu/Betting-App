<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    
    Route::prefix('user')->group(function(){
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/', [UserController::class, 'user']);
        Route::post('/delete', [UserController::class, 'deleteAccount']);
        Route::post('/', [UserController::class, 'update']);
    });

    // Subscription routes
    Route::prefix('plan')->group(function() {
        Route::get('/', [SubscriptionController::class, 'index']);
        Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
    });

    // Ticket routes
    Route::prefix('ticket')->group(function(){
        Route::post('/', [TicketController::class, 'store']);
        Route::get('/', [TicketController::class, 'index']);
        Route::get('/history', [TicketController::class, 'history']);
        Route::get('/favorites', [TicketController::class, 'favorites']);
        Route::post('/clear-history', [TicketController::class, 'clearHistory']);
        Route::get('/deleted', [TicketController::class, 'deleted']);
        Route::post('/{id}/restore', [TicketController::class, 'restore']);
        Route::get('/{ticket}', [TicketController::class, 'show']);
        Route::post('/{ticket}/favorite', [TicketController::class, 'toggleFavorite']);
        Route::get('/{ticket}/delete', [TicketController::class, 'delete']);
    });
});
