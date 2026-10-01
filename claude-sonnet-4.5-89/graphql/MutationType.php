<?php

namespace app\graphql;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use app\models\User;
use app\models\ApiRoute;
use Yii;

class MutationType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Mutation',
            'fields' => [
                'createRoute' => [
                    'type' => TypeRegistry::apiRouteType(),
                    'args' => [
                        'name' => Type::nonNull(Type::string()),
                        'path' => Type::nonNull(Type::string()),
                        'method' => Type::nonNull(Type::string()),
                        'target_url' => Type::nonNull(Type::string()),
                        'enabled' => ['type' => Type::boolean(), 'defaultValue' => true],
                        'rate_limit' => ['type' => Type::int(), 'defaultValue' => 100],
                    ],
                    'resolve' => function($root, $args) {
                        $this->checkAdmin();
                        
                        $route = new ApiRoute();
                        $route->name = $args['name'];
                        $route->path = $args['path'];
                        $route->method = $args['method'];
                        $route->target_url = $args['target_url'];
                        $route->enabled = $args['enabled'];
                        $route->rate_limit = $args['rate_limit'];
                        
                        if (!$route->save()) {
                            throw new \Exception('Failed to create route');
                        }
                        
                        return $route;
                    }
                ],
                'updateRoute' => [
                    'type' => TypeRegistry::apiRouteType(),
                    'args' => [
                        'id' => Type::nonNull(Type::int()),
                        'name' => Type::string(),
                        'path' => Type::string(),
                        'method' => Type::string(),
                        'target_url' => Type::string(),
                        'enabled' => Type::boolean(),
                        'rate_limit' => Type::int(),
                    ],
                    'resolve' => function($root, $args) {
                        $this->checkAdmin();
                        
                        $route = ApiRoute::findOne($args['id']);
                        if (!$route) {
                            throw new \Exception('Route not found');
                        }
                        
                        if (isset($args['name'])) $route->name = $args['name'];
                        if (isset($args['path'])) $route->path = $args['path'];
                        if (isset($args['method'])) $route->method = $args['method'];
                        if (isset($args['target_url'])) $route->target_url = $args['target_url'];
                        if (isset($args['enabled'])) $route->enabled = $args['enabled'];
                        if (isset($args['rate_limit'])) $route->rate_limit = $args['rate_limit'];
                        
                        if (!$route->save()) {
                            throw new \Exception('Failed to update route');
                        }
                        
                        return $route;
                    }
                ],
                'deleteRoute' => [
                    'type' => Type::boolean(),
                    'args' => [
                        'id' => Type::nonNull(Type::int()),
                    ],
                    'resolve' => function($root, $args) {
                        $this->checkAdmin();
                        
                        $route = ApiRoute::findOne($args['id']);
                        if (!$route) {
                            throw new \Exception('Route not found');
                        }
                        
                        return $route->delete() !== false;
                    }
                ],
            ],
        ]);
    }

    private function checkAdmin()
    {
        if (Yii::$app->user->isGuest) {
            throw new \Exception('Authentication required');
        }
        
        if (Yii::$app->user->identity->role !== 'admin') {
            throw new \Exception('Admin access required');
        }
    }
}