<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');

        if (!$header) {
            return service('response')->setJSON([
                'status' => 'error',
                'message' => 'Authorization token required'
            ])->setStatusCode(401);
        }

        $token = null;
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            $token = $matches[1];
        }

        if (!$token) {
            return service('response')->setJSON([
                'status' => 'error',
                'message' => 'Invalid authorization format'
            ])->setStatusCode(401);
        }

        try {
            $secret = getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production-2024';
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            
            $request->user = [
                'id' => $decoded->sub,
                'username' => $decoded->username,
                'email' => $decoded->email,
                'role' => $decoded->role
            ];

        } catch (Exception $e) {
            return service('response')->setJSON([
                'status' => 'error',
                'message' => 'Invalid or expired token'
            ])->setStatusCode(401);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do here
    }
}