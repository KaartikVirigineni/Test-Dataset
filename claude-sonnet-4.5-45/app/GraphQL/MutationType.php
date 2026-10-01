<?php

namespace App\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use App\Models\UserModel;
use App\Models\ProjectModel;
use App\Models\DocumentationModel;
use App\Libraries\JWTLibrary;

class MutationType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Mutation',
            'fields' => [
                'register' => [
                    'type' => Types::authPayload(),
                    'args' => [
                        'email' => Type::nonNull(Type::string()),
                        'password' => Type::nonNull(Type::string()),
                        'name' => Type::nonNull(Type::string())
                    ],
                    'resolve' => function($root, $args) {
                        $model = new UserModel();
                        $data = [
                            'email' => $args['email'],
                            'password' => password_hash($args['password'], PASSWORD_DEFAULT),
                            'name' => $args['name'],
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        
                        $userId = $model->insert($data);
                        $user = $model->find($userId);
                        
                        $jwt = new JWTLibrary();
                        $token = $jwt->encode([
                            'user_id' => $user['id'],
                            'email' => $user['email']
                        ]);
                        
                        return [
                            'token' => $token,
                            'user' => $user
                        ];
                    }
                ],
                'login' => [
                    'type' => Types::authPayload(),
                    'args' => [
                        'email' => Type::nonNull(Type::string()),
                        'password' => Type::nonNull(Type::string())
                    ],
                    'resolve' => function($root, $args) {
                        $model = new UserModel();
                        $user = $model->where('email', $args['email'])->first();
                        
                        if (!$user || !password_verify($args['password'], $user['password'])) {
                            throw new \Exception('Invalid credentials');
                        }
                        
                        $jwt = new JWTLibrary();
                        $token = $jwt->encode([
                            'user_id' => $user['id'],
                            'email' => $user['email']
                        ]);
                        
                        return [
                            'token' => $token,
                            'user' => $user
                        ];
                    }
                ],
                'createProject' => [
                    'type' => Types::project(),
                    'args' => [
                        'name' => Type::nonNull(Type::string()),
                        'description' => Type::nonNull(Type::string()),
                        'repository_url' => Type::string(),
                        'status' => Type::string()
                    ],
                    'resolve' => function($root, $args) {
                        $model = new ProjectModel();
                        $data = [
                            'name' => $args['name'],
                            'description' => $args['description'],
                            'repository_url' => $args['repository_url'] ?? null,
                            'status' => $args['status'] ?? 'active',
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        
                        $id = $model->insert($data);
                        return $model->find($id);
                    }
                ],
                'createDocumentation' => [
                    'type' => Types::documentation(),
                    'args' => [
                        'title' => Type::nonNull(Type::string()),
                        'content' => Type::nonNull(Type::string()),
                        'project_id' => Type::nonNull(Type::int()),
                        'category' => Type::string()
                    ],
                    'resolve' => function($root, $args) {
                        $model = new DocumentationModel();
                        $data = [
                            'title' => $args['title'],
                            'content' => $args['content'],
                            'project_id' => $args['project_id'],
                            'category' => $args['category'] ?? 'general',
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        
                        $id = $model->insert($data);
                        return $model->find($id);
                    }
                ]
            ]
        ]);
    }
}