<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;
use app\models\User;

class AuthController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['login', 'register'],
        ];
        $behaviors['verbFilter'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'login' => ['post'],
                'register' => ['post'],
            ],
        ];
        return $behaviors;
    }

    public function actionLogin()
    {
        $request = Yii::$app->request->post();
        
        $username = $request['username'] ?? null;
        $password = $request['password'] ?? null;

        if (!$username || !$password) {
            Yii::$app->response->statusCode = 400;
            return ['error' => 'Username and password are required'];
        }

        $user = User::findOne(['username' => $username]);

        if (!$user || !$user->validatePassword($password)) {
            Yii::$app->response->statusCode = 401;
            return ['error' => 'Invalid credentials'];
        }

        $token = $user->generateAccessToken();

        return [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ];
    }

    public function actionRegister()
    {
        $request = Yii::$app->request->post();
        
        $user = new User();
        $user->username = $request['username'] ?? null;
        $user->email = $request['email'] ?? null;
        $user->password_hash = password_hash($request['password'] ?? '', PASSWORD_DEFAULT);
        $user->role = 'user';

        if ($user->save()) {
            $token = $user->generateAccessToken();
            
            return [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ];
        }

        Yii::$app->response->statusCode = 400;
        return ['errors' => $user->errors];
    }
}