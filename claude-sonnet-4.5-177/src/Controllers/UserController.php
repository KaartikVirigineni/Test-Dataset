<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Database\Database;

class UserController
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getUser(Request $request, Response $response, array $args): Response
    {
        $userId = (int)$args['id'];

        $stmt = $this->db->getConnection()->prepare(
            'SELECT id, username, email, created_at FROM users WHERE id = ?'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $user['id'] = (int)$user['id'];

        $response->getBody()->write(json_encode($user));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function followUser(Request $request, Response $response, array $args): Response
    {
        $followingId = (int)$args['id'];
        $followerId = $request->getAttribute('user_id');

        if ($followerId === $followingId) {
            $response->getBody()->write(json_encode(['error' => 'Cannot follow yourself']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $stmt = $this->db->getConnection()->prepare(
                'INSERT INTO follows (follower_id, following_id) VALUES (?, ?)'
            );
            $stmt->execute([$followerId, $followingId]);

            $response->getBody()->write(json_encode(['message' => 'Successfully followed user']));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Already following this user']));
            return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
        }
    }

    public function unfollowUser(Request $request, Response $response, array $args): Response
    {
        $followingId = (int)$args['id'];
        $followerId = $request->getAttribute('user_id');

        $stmt = $this->db->getConnection()->prepare(
            'DELETE FROM follows WHERE follower_id = ? AND following_id = ?'
        );
        $stmt->execute([$followerId, $followingId]);

        if ($stmt->rowCount() === 0) {
            $response->getBody()->write(json_encode(['error' => 'Not following this user']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['message' => 'Successfully unfollowed user']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getFollowers(Request $request, Response $response, array $args): Response
    {
        $userId = (int)$args['id'];

        $stmt = $this->db->getConnection()->prepare(
            'SELECT u.id, u.username, u.email, f.created_at as followed_at 
             FROM follows f 
             JOIN users u ON f.follower_id = u.id 
             WHERE f.following_id = ?
             ORDER BY f.created_at DESC'
        );
        $stmt->execute([$userId]);
        $followers = $stmt->fetchAll();

        foreach ($followers as &$follower) {
            $follower['id'] = (int)$follower['id'];
        }

        $response->getBody()->write(json_encode($followers));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getFollowing(Request $request, Response $response, array $args): Response
    {
        $userId = (int)$args['id'];

        $stmt = $this->db->getConnection()->prepare(
            'SELECT u.id, u.username, u.email, f.created_at as followed_at 
             FROM follows f 
             JOIN users u ON f.following_id = u.id 
             WHERE f.follower_id = ?
             ORDER BY f.created_at DESC'
        );
        $stmt->execute([$userId]);
        $following = $stmt->fetchAll();

        foreach ($following as &$user) {
            $user['id'] = (int)$user['id'];
        }

        $response->getBody()->write(json_encode($following));
        return $response->withHeader('Content-Type', 'application/json');
    }
}