<?php

namespace App\Controllers;

use App\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    public function list(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        
        if ($user['role'] !== 'admin') {
            $response->getBody()->write(json_encode(['error' => 'Admin access required']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        $response->getBody()->write(json_encode($users));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        
        if ($user['role'] !== 'admin' && $user['user_id'] != $args['id']) {
            $response->getBody()->write(json_encode(['error' => 'Access denied']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = :id");
        $stmt->execute(['id' => $args['id']]);
        $userData = $stmt->fetch();
        
        if (!$userData) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode($userData));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function updateRole(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        
        if ($user['role'] !== 'admin') {
            $response->getBody()->write(json_encode(['error' => 'Admin access required']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        $data = $request->getParsedBody();
        
        if (!isset($data['role']) || !in_array($data['role'], ['user', 'admin'])) {
            $response->getBody()->write(json_encode(['error' => 'Valid role required (user or admin)']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET role = :role WHERE id = :id");
        $stmt->execute(['role' => $data['role'], 'id' => $args['id']]);
        
        if ($stmt->rowCount() === 0) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $stmt = $db->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = :id");
        $stmt->execute(['id' => $args['id']]);
        $userData = $stmt->fetch();
        
        $response->getBody()->write(json_encode($userData));
        return $response->withHeader('Content-Type', 'application/json');
    }
}