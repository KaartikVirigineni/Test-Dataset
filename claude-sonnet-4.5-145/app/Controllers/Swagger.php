<?php

namespace App\Controllers;

class Swagger extends BaseController
{
    public function index()
    {
        $this->response->setHeader('Content-Type', 'application/x-yaml');
        $openapi = file_get_contents(ROOTPATH . 'openapi.yaml');
        return $this->response->setBody($openapi);
    }

    public function ui()
    {
        return view('swagger_ui');
    }
}