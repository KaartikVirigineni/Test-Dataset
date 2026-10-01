<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Database\Database;
use Firebase\JWT\JWT;

class AuthController
{
    private Database $db;
    private string $jwtSecret;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->jwtSecret = getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production';
    }

    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $username = $data['username'];
        $email = $data['email'];
        $password = $data['password'];

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $this->db->getConnection()->prepare(
                'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)'
            );
            $stmt->execute([$username, $email, $passwordHash]);

            $userId = $this->db->getConnection()->lastInsertId();

            $token = $this->generateToken($userId);

            $response->getBody()->write(json_encode([
                'id' => (int)$userId,
                'username' => $username,
                'email' => $email,
                'token' => $token
            ]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Username or email already exists']));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (empty($data['username']) || empty($data['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Missing username or password']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $username = $data['username'];
        $password = $data['password'];

        $stmt = $this->db->getConnection()->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $token = $this->generateToken($user['id']);

        $response->getBody()->write(json_encode([
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'token' => $token
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function generateToken(int $userId): string
    {
        $payload = [
            'user_id' => $userId,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24 * 7)
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }
}