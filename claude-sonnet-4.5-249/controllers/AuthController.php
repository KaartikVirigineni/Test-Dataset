<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\ContentNegotiator;
use yii\web\Response;
use app\models\User;
use Firebase\JWT\JWT;

class AuthController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];
        return $behaviors;
    }

    public function actionRegister()
    {
        $data = Yii::$app->request->post();
        
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            Yii::$app->response->statusCode = 400;
            return ['error' => 'Username, email and password are required'];
        }

        $user = new User();
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->setPassword($data['password']);
        $user->status = User::STATUS_ACTIVE;

        if ($user->save()) {
            $auth = Yii::$app->authManager;
            $role = $auth->getRole('user');
            $auth->assign($role, $user->id);

            Yii::$app->response->statusCode = 201;
            return [
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                ]
            ];
        }

        Yii::$app->response->statusCode = 400;
        return ['errors' => $user->errors];
    }

    public function actionLogin()
    {
        $data = Yii::$app->request->post();
        
        if (empty($data['username']) || empty($data['password'])) {
            Yii::$app->response->statusCode = 400;
            return ['error' => 'Username and password are required'];
        }

        $user = User::findByUsername($data['username']);
        
        if (!$user || !$user->validatePassword($data['password'])) {
            Yii::$app->response->statusCode = 401;
            return ['error' => 'Invalid username or password'];
        }

        $payload = [
            'iss' => 'realtime-presence',
            'iat' => time(),
            'exp' => time() + Yii::$app->params['jwtExpire'],
            'sub' => $user->id,
            'username' => $user->username,
        ];

        $token = JWT::encode($payload, Yii::$app->params['jwtSecret'], 'HS256');

        return [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ]
        ];
    }
}