<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwaggerController;

Route::get('/', function () {
    return response()->json([
        'message' => 'DocuHub API',
        'version' => '1.0.0',
        'docs' => url('/swagger-ui'),
    ]);
});

Route::get('/swagger', [SwaggerController::class, 'getSpec']);
Route::get('/swagger-ui', [SwaggerController::class, 'ui']);