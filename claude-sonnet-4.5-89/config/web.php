<?php

return [
    'id' => 'api-gateway-admin',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'sqlite:' . dirname(__DIR__) . '/data/database.db',
        ],
        'request' => [
            'enableCookieValidation' => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => false,
            'enableSession' => false,
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'POST auth/login' => 'auth/login',
                'POST auth/register' => 'auth/register',
                'GET swagger' => 'site/swagger',
                'GET docs' => 'site/docs',
                'POST graphql' => 'graphql/index',
                'GET api/routes' => 'api/routes',
                'POST api/routes' => 'api/create-route',
                'PUT api/routes/<id:\d+>' => 'api/update-route',
                'DELETE api/routes/<id:\d+>' => 'api/delete-route',
                'GET api/users' => 'api/users',
                'GET api/logs' => 'api/logs',
            ],
        ],
    ],
    'modules' => [],
];