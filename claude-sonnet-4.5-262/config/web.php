<?php

$config = [
    'id' => 'api-gateway-admin',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'secure-random-key-change-in-production',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            'enableCsrfValidation' => false,
        ],
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                if ($response->data !== null && !is_string($response->data)) {
                    $response->data = $response->data;
                } elseif ($response->statusCode >= 400) {
                    $response->data = [
                        'success' => false,
                        'error' => $response->statusText,
                    ];
                }
            },
        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'sqlite:' . __DIR__ . '/../db/database.db',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => false,
            'enableSession' => false,
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => false,
            'showScriptName' => false,
            'rules' => [
                'GET health' => 'site/health',
                'POST auth/login' => 'auth/login',
                'POST auth/register' => 'auth/register',
                'GET swagger' => 'site/swagger',
                'GET docs' => 'site/docs',
                
                'GET api-keys' => 'api-key/index',
                'POST api-keys' => 'api-key/create',
                'GET api-keys/<id:\d+>' => 'api-key/view',
                'PUT api-keys/<id:\d+>' => 'api-key/update',
                'DELETE api-keys/<id:\d+>' => 'api-key/delete',
                
                'GET routes' => 'route/index',
                'POST routes' => 'route/create',
                'GET routes/<id:\d+>' => 'route/view',
                'PUT routes/<id:\d+>' => 'route/update',
                'DELETE routes/<id:\d+>' => 'route/delete',
                
                'GET users' => 'user/index',
                'GET users/<id:\d+>' => 'user/view',
                'PUT users/<id:\d+>' => 'user/update',
                'DELETE users/<id:\d+>' => 'user/delete',
                
                'GET roles' => 'role/index',
                'POST roles' => 'role/create',
                'DELETE roles/<name>' => 'role/delete',
                'POST roles/assign' => 'role/assign',
                'POST roles/revoke' => 'role/revoke',
            ],
        ],
    ],
];

return $config;