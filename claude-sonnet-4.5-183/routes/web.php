<?php

use Illuminate\Support\Facades\Route;

Route::get('/swagger', function () {
    return response()->file(base_path('openapi.yaml'), [
        'Content-Type' => 'application/yaml'
    ]);
});

Route::get('/docs', function () {
    return view('swagger-ui');
});