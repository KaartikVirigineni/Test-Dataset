<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SwaggerController extends Controller
{
    public function getSpec()
    {
        $path = base_path('openapi.yaml');
        
        if (!file_exists($path)) {
            abort(404, 'OpenAPI specification not found');
        }

        return response()->file($path, [
            'Content-Type' => 'application/yaml',
        ]);
    }

    public function ui()
    {
        return view('swagger-ui');
    }
}