<?php

$db = require __DIR__ . '/db.php';

return [
    'id' => 'realtime-presence',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'realtime-presence-secret-key-change-in-production',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            'enableCsrfValidation' => false,
        ],
        'response' => [
            'class' => 'yii\web\Response',
            'format' => yii\web\Response::FORMAT_JSON,
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                if ($response->data !== null && !is_string($response->data)) {
                    $response->data = $response->data;
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
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                ['class' => 'yii\rest\UrlRule', 'controller' => 'api/user', 'pluralize' => false],
                ['class' => 'yii\rest\UrlRule', 'controller' => 'api/presence', 'pluralize' => false],
                'POST auth/register' => 'auth/register',
                'POST auth/login' => 'auth/login',
                'GET swagger' => 'site/swagger',
                'GET docs' => 'site/docs',
                'GET health' => 'site/health',
            ],
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            'cache' => 'cache',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
    ],
    'params' => [
        'jwtSecret' => 'your-secret-jwt-key-change-in-production',
        'jwtExpire' => 3600,
    ],
];