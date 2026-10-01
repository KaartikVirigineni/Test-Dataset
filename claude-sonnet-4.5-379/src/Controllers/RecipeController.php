<?php

namespace App\Controllers;

use App\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RecipeController
{
    public function list(Request $request, Response $response): Response
    {
        $db = Database::getConnection();
        $user = $request->getAttribute('user');
        
        if ($user['role'] === 'admin') {
            $stmt = $db->prepare("
                SELECT r.*, u.username as author 
                FROM recipes r 
                LEFT JOIN users u ON r.user_id = u.id 
                ORDER BY r.created_at DESC
            ");
            $stmt->execute();
        } else {
            $stmt = $db->prepare("
                SELECT r.*, u.username as author 
                FROM recipes r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.user_id = :user_id 
                ORDER BY r.created_at DESC
            ");
            $stmt->execute(['user_id' => $user['user_id']]);
        }
        
        $recipes = $stmt->fetchAll();
        
        $response->getBody()->write(json_encode($recipes));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $db = Database::getConnection();
        $user = $request->getAttribute('user');
        
        $stmt = $db->prepare("
            SELECT r.*, u.username as author 
            FROM recipes r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.id = :id
        ");
        $stmt->execute(['id' => $args['id']]);
        $recipe = $stmt->fetch();
        
        if (!$recipe) {
            $response->getBody()->write(json_encode(['error' => 'Recipe not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        if ($user['role'] !== 'admin' && $recipe['user_id'] != $user['user_id']) {
            $response->getBody()->write(json_encode(['error' => 'Access denied']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode($recipe));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');
        
        if (!isset($data['title']) || !isset($data['ingredients']) || !isset($data['instructions'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Title, ingredients, and instructions are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO recipes (user_id, title, description, ingredients, instructions, prep_time, cook_time, servings) 
            VALUES (:user_id, :title, :description, :ingredients, :instructions, :prep_time, :cook_time, :servings)
        ");
        
        $stmt->execute([
            'user_id' => $user['user_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'ingredients' => $data['ingredients'],
            'instructions' => $data['instructions'],
            'prep_time' => $data['prep_time'] ?? null,
            'cook_time' => $data['cook_time'] ?? null,
            'servings' => $data['servings'] ?? null
        ]);
        
        $recipeId = $db->lastInsertId();
        
        $stmt = $db->prepare("SELECT * FROM recipes WHERE id = :id");
        $stmt->execute(['id' => $recipeId]);
        $recipe = $stmt->fetch();
        
        $response->getBody()->write(json_encode($recipe));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');
        $db = Database::getConnection();
        
        $stmt = $db->prepare("SELECT * FROM recipes WHERE id = :id");
        $stmt->execute(['id' => $args['id']]);
        $recipe = $stmt->fetch();
        
        if (!$recipe) {
            $response->getBody()->write(json_encode(['error' => 'Recipe not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        if ($user['role'] !== 'admin' && $recipe['user_id'] != $user['user_id']) {
            $response->getBody()->write(json_encode(['error' => 'Access denied']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        $stmt = $db->prepare("
            UPDATE recipes 
            SET title = :title, 
                description = :description, 
                ingredients = :ingredients, 
                instructions = :instructions,
                prep_time = :prep_time,
                cook_time = :cook_time,
                servings = :servings,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        
        $stmt->execute([
            'id' => $args['id'],
            'title' => $data['title'] ?? $recipe['title'],
            'description' => $data['description'] ?? $recipe['description'],
            'ingredients' => $data['ingredients'] ?? $recipe['ingredients'],
            'instructions' => $data['instructions'] ?? $recipe['instructions'],
            'prep_time' => $data['prep_time'] ?? $recipe['prep_time'],
            'cook_time' => $data['cook_time'] ?? $recipe['cook_time'],
            'servings' => $data['servings'] ?? $recipe['servings']
        ]);
        
        $stmt = $db->prepare("SELECT * FROM recipes WHERE id = :id");
        $stmt->execute(['id' => $args['id']]);
        $updated = $stmt->fetch();
        
        $response->getBody()->write(json_encode($updated));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $db = Database::getConnection();
        
        $stmt = $db->prepare("SELECT * FROM recipes WHERE id = :id");
        $stmt->execute(['id' => $args['id']]);
        $recipe = $stmt->fetch();
        
        if (!$recipe) {
            $response->getBody()->write(json_encode(['error' => 'Recipe not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        if ($user['role'] !== 'admin' && $recipe['user_id'] != $user['user_id']) {
            $response->getBody()->write(json_encode(['error' => 'Access denied']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        $stmt = $db->prepare("DELETE FROM recipes WHERE id = :id");
        $stmt->execute(['id' => $args['id']]);
        
        $response->getBody()->write(json_encode(['message' => 'Recipe deleted successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}