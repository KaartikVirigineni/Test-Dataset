<?php

namespace App\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use App\Models\UserModel;
use App\Models\AnalyticsModel;
use App\Libraries\JWTHelper;

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
                        'name' => Type::string(),
                    ],
                    'resolve' => function ($root, $args) {
                        $userModel = new UserModel();
                        
                        if ($userModel->where('email', $args['email'])->first()) {
                            throw new \Exception('User already exists');
                        }
                        
                        $userId = $userModel->insert([
                            'email' => $args['email'],
                            'password' => password_hash($args['password'], PASSWORD_DEFAULT),
                            'name' => $args['name'] ?? null,
                        ]);
                        
                        $user = $userModel->find($userId);
                        $jwtHelper = new JWTHelper();
                        $token = $jwtHelper->generateToken($user);
                        
                        return [
                            'token' => $token,
                            'user' => [
                                'id' => $user['id'],
                                'email' => $user['email'],
                                'name' => $user['name'],
                            ],
                        ];
                    },
                ],
                'login' => [
                    'type' => Types::authPayload(),
                    'args' => [
                        'email' => Type::nonNull(Type::string()),
                        'password' => Type::nonNull(Type::string()),
                    ],
                    'resolve' => function ($root, $args) {
                        $userModel = new UserModel();
                        $user = $userModel->where('email', $args['email'])->first();
                        
                        if (!$user || !password_verify($args['password'], $user['password'])) {
                            throw new \Exception('Invalid credentials');
                        }
                        
                        $jwtHelper = new JWTHelper();
                        $token = $jwtHelper->generateToken($user);
                        
                        return [
                            'token' => $token,
                            'user' => [
                                'id' => $user['id'],
                                'email' => $user['email'],
                                'name' => $user['name'],
                            ],
                        ];
                    },
                ],
                'createAnalytic' => [
                    'type' => Types::analytic(),
                    'args' => [
                        'input' => Type::nonNull(Types::analyticInput()),
                    ],
                    'resolve' => function ($root, $args, $context) {
                        if (!isset($context['user'])) {
                            throw new \Exception('Authentication required');
                        }
                        
                        $model = new AnalyticsModel();
                        
                        $data = [
                            'user_id' => $context['user']['id'],
                            'metric_name' => $args['input']['metric_name'],
                            'value' => $args['input']['value'],
                            'metadata' => $args['input']['metadata'] ?? null,
                            'timestamp' => date('Y-m-d H:i:s'),
                        ];
                        
                        $id = $model->insert($data);
                        return $model->find($id);
                    },
                ],
                'updateAnalytic' => [
                    'type' => Types::analytic(),
                    'args' => [
                        'id' => Type::nonNull(Type::int()),
                        'input' => Type::nonNull(Types::analyticInput()),
                    ],
                    'resolve' => function ($root, $args, $context) {
                        if (!isset($context['user'])) {
                            throw new \Exception('Authentication required');
                        }
                        
                        $model = new AnalyticsModel();
                        $analytic = $model
                            ->where('id', $args['id'])
                            ->where('user_id', $context['user']['id'])
                            ->first();
                        
                        if (!$analytic) {
                            throw new \Exception('Analytics record not found');
                        }
                        
                        $data = [
                            'metric_name' => $args['input']['metric_name'],
                            'value' => $args['input']['value'],
                            'metadata' => $args['input']['metadata'] ?? null,
                        ];
                        
                        $model->update($args['id'], $data);
                        return $model->find($args['id']);
                    },
                ],
                'deleteAnalytic' => [
                    'type' => Type::boolean(),
                    'args' => [
                        'id' => Type::nonNull(Type::int()),
                    ],
                    'resolve' => function ($root, $args, $context) {
                        if (!isset($context['user'])) {
                            throw new \Exception('Authentication required');
                        }
                        
                        $model = new AnalyticsModel();
                        $analytic = $model
                            ->where('id', $args['id'])
                            ->where('user_id', $context['user']['id'])
                            ->first();
                        
                        if (!$analytic) {
                            throw new \Exception('Analytics record not found');
                        }
                        
                        return $model->delete($args['id']);
                    },
                ],
            ],
        ]);
    }
}