<?php

namespace App\Controllers;

class Swagger extends BaseController
{
    public function index()
    {
        $yamlPath = ROOTPATH . 'openapi.yaml';
        
        if (!file_exists($yamlPath)) {
            return $this->errorResponse('OpenAPI spec not found', 404);
        }

        return $this->response
            ->setContentType('application/yaml')
            ->setBody(file_get_contents($yamlPath));
    }

    public function ui()
    {
        return view('swagger_ui');
    }
}