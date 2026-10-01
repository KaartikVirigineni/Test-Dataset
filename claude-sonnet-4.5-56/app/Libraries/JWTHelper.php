<?php

namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHelper
{
    protected $secretKey;
    protected $expiration;

    public function __construct()
    {
        $this->secretKey = getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production-environment';
        $this->expiration = getenv('JWT_EXPIRATION') ?: 3600;
    }

    public function generateToken($user)
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->expiration;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'sub' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            
            return [
                'id' => $decoded->sub,
                'email' => $decoded->email,
                'name' => $decoded->name,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }
}