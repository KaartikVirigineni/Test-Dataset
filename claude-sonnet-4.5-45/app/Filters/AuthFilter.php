<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\JWTLibrary;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $jwt = new JWTLibrary();
        $header = $request->getHeaderLine('Authorization');
        
        if (empty($header)) {
            return service('response')
                ->setJSON(['error' => 'No authorization token provided'])
                ->setStatusCode(401);
        }
        
        $token = str_replace('Bearer ', '', $header);
        
        try {
            $decoded = $jwt->decode($token);
            $request->user_id = $decoded->user_id;
        } catch (\Exception $e) {
            return service('response')
                ->setJSON(['error' => 'Invalid or expired token'])
                ->setStatusCode(401);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}