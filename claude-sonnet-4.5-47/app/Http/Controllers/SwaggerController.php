<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

class SwaggerController extends Controller
{
    public function getSpec()
    {
        $spec = File::get(base_path('openapi.yaml'));
        return response($spec, 200)->header('Content-Type', 'application/yaml');
    }

    public function getUI()
    {
        return view('swagger-ui');
    }
}