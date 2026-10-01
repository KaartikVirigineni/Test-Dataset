<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Database\Database;

class PostController
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function createPost(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $userId = $request->getAttribute('user_id');

        if (empty($data['content'])) {
            $response->getBody()->write(json_encode(['error' => 'Content is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $content = $data['content'];

        if (strlen($content) > 280) {
            $response->getBody()->write(json_encode(['error' => 'Content must be 280 characters or less']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $this->db->getConnection()->prepare(
            'INSERT INTO posts (user_id, content) VALUES (?, ?)'
        );
        $stmt->execute([$userId, $content]);

        $postId = $this->db->getConnection()->lastInsertId();

        $stmt = $this->db->getConnection()->prepare(
            'SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?'
        );
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        $post['id'] = (int)$post['id'];
        $post['user_id'] = (int)$post['user_id'];

        $response->getBody()->write(json_encode($post));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function getPosts(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? min((int)$params['limit'], 100) : 50;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        $userId = isset($params['user_id']) ? (int)$params['user_id'] : null;

        $sql = 'SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id';
        $sqlParams = [];

        if ($userId) {
            $sql .= ' WHERE p.user_id = ?';
            $sqlParams[] = $userId;
        }

        $sql .= ' ORDER BY p.created_at DESC LIMIT ? OFFSET ?';
        $sqlParams[] = $limit;
        $sqlParams[] = $offset;

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($sqlParams);
        $posts = $stmt->fetchAll();

        foreach ($posts as &$post) {
            $post['id'] = (int)$post['id'];
            $post['user_id'] = (int)$post['user_id'];
        }

        $response->getBody()->write(json_encode($posts));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getPost(Request $request, Response $response, array $args): Response
    {
        $postId = (int)$args['id'];

        $stmt = $this->db->getConnection()->prepare(
            'SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?'
        );
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if (!$post) {
            $response->getBody()->write(json_encode(['error' => 'Post not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $post['id'] = (int)$post['id'];
        $post['user_id'] = (int)$post['user_id'];

        $response->getBody()->write(json_encode($post));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function deletePost(Request $request, Response $response, array $args): Response
    {
        $postId = (int)$args['id'];
        $userId = $request->getAttribute('user_id');

        $stmt = $this->db->getConnection()->prepare('SELECT user_id FROM posts WHERE id = ?');
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if (!$post) {
            $response->getBody()->write(json_encode(['error' => 'Post not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if ((int)$post['user_id'] !== $userId) {
            $response->getBody()->write(json_encode(['error' => 'Not authorized to delete this post']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $this->db->getConnection()->prepare('DELETE FROM posts WHERE id = ?');
        $stmt->execute([$postId]);

        $response->getBody()->write(json_encode(['message' => 'Post deleted successfully']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getFeed(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $params = $request->getQueryParams();
        $limit = isset($params['limit']) ? min((int)$params['limit'], 100) : 50;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;

        $stmt = $this->db->getConnection()->prepare(
            'SELECT p.*, u.username 
             FROM posts p 
             JOIN users u ON p.user_id = u.id 
             WHERE p.user_id IN (
                 SELECT following_id FROM follows WHERE follower_id = ?
             ) OR p.user_id = ?
             ORDER BY p.created_at DESC 
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$userId, $userId, $limit, $offset]);
        $posts = $stmt->fetchAll();

        foreach ($posts as &$post) {
            $post['id'] = (int)$post['id'];
            $post['user_id'] = (int)$post['user_id'];
        }

        $response->getBody()->write(json_encode($posts));
        return $response->withHeader('Content-Type', 'application/json');
    }
}