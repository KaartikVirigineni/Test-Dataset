<?php

namespace app\graphql;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use app\models\User;
use app\models\ApiRoute;
use app\models\RequestLog;
use Yii;

class QueryType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Query',
            'fields' => [
                'users' => [
                    'type' => Type::listOf(TypeRegistry::userType()),
                    'resolve' => function() {
                        $this->checkAuth();
                        return User::find()->all();
                    }
                ],
                'user' => [
                    'type' => TypeRegistry::userType(),
                    'args' => [
                        'id' => Type::nonNull(Type::int()),
                    ],
                    'resolve' => function($root, $args) {
                        $this->checkAuth();
                        return User::findOne($args['id']);
                    }
                ],
                'routes' => [
                    'type' => Type::listOf(TypeRegistry::apiRouteType()),
                    'resolve' => function() {
                        $this->checkAuth();
                        return ApiRoute::find()->all();
                    }
                ],
                'route' => [
                    'type' => TypeRegistry::apiRouteType(),
                    'args' => [
                        'id' => Type::nonNull(Type::int()),
                    ],
                    'resolve' => function($root, $args) {
                        $this->checkAuth();
                        return ApiRoute::findOne($args['id']);
                    }
                ],
                'logs' => [
                    'type' => Type::listOf(TypeRegistry::requestLogType()),
                    'args' => [
                        'limit' => ['type' => Type::int(), 'defaultValue' => 100],
                    ],
                    'resolve' => function($root, $args) {
                        $this->checkAuth();
                        return RequestLog::find()
                            ->orderBy(['created_at' => SORT_DESC])
                            ->limit($args['limit'])
                            ->all();
                    }
                ],
            ],
        ]);
    }

    private function checkAuth()
    {
        if (Yii::$app->user->isGuest) {
            throw new \Exception('Authentication required');
        }
    }
}