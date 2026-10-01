<?php

$db = require __DIR__ . '/db.php';

return [
    'id' => 'reservetable',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'reservetable-secret-key-change-in-production',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            'enableCsrfValidation' => false,
        ],
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                if ($response->data !== null) {
                    if (isset($response->data['success'])) {
                        return;
                    }
                    if ($response->isSuccessful) {
                        $response->data = [
                            'success' => true,
                            'data' => $response->data,
                        ];
                    } else {
                        $response->data = [
                            'success' => false,
                            'error' => $response->data,
                        ];
                    }
                }
            },
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => false,
            'enableSession' => false,
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
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => false,
            'showScriptName' => false,
            'rules' => [
                'POST auth/register' => 'auth/register',
                'POST auth/login' => 'auth/login',
                'GET swagger' => 'site/swagger',
                'GET swagger-ui' => 'site/swagger-ui',
                
                'GET reservations' => 'reservation/index',
                'POST reservations' => 'reservation/create',
                'GET reservations/<id:\d+>' => 'reservation/view',
                'PUT reservations/<id:\d+>' => 'reservation/update',
                'DELETE reservations/<id:\d+>' => 'reservation/delete',
            ],
        ],
    ],
    'params' => [
        'jwtSecret' => 'your-secret-jwt-key-change-in-production-min-256-bits-long',
    ],
];