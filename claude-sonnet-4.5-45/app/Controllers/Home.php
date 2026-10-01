<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        return json_encode([
            'name' => 'DevPortal',
            'version' => '1.0.0',
            'endpoints' => [
                'REST API' => '/api',
                'GraphQL' => '/graphql',
                'Swagger Spec' => '/swagger',
                'API Documentation' => '/docs'
            ]
        ]);
    }

    public function swagger()
    {
        $filePath = ROOTPATH . 'openapi.yaml';
        
        if (!file_exists($filePath)) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON(['error' => 'OpenAPI spec not found']);
        }

        return $this->response
            ->setHeader('Content-Type', 'application/yaml')
            ->setBody(file_get_contents($filePath));
    }

    public function swaggerUI(): string
    {
        return view('swagger_ui');
    }
}