<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WebhookController;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    Route::get('/webhooks', [WebhookController::class, 'index']);
    Route::get('/webhooks/{id}', [WebhookController::class, 'show']);
    Route::delete('/webhooks/{id}', [WebhookController::class, 'destroy']);
    Route::get('/endpoints', [WebhookController::class, 'endpoints']);
    Route::post('/endpoints', [WebhookController::class, 'createEndpoint']);
    Route::delete('/endpoints/{id}', [WebhookController::class, 'destroyEndpoint']);
});

Route::post('/catch/{endpoint_id}', [WebhookController::class, 'catch']);
Route::get('/catch/{endpoint_id}', [WebhookController::class, 'catch']);
Route::put('/catch/{endpoint_id}', [WebhookController::class, 'catch']);
Route::patch('/catch/{endpoint_id}', [WebhookController::class, 'catch']);
Route::delete('/catch/{endpoint_id}', [WebhookController::class, 'catch']);