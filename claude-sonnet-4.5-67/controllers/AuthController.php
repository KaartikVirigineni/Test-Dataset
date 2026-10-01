<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;
use app\models\User;
use app\components\JwtHelper;

class AuthController extends Controller
{
    public function actionRegister()
    {
        $data = Yii::$app->request->post();

        if (!isset($data['username']) || !isset($data['password'])) {
            throw new BadRequestHttpException('Username and password are required');
        }

        $user = new User();
        $user->username = $data['username'];
        $user->setPassword($data['password']);

        if (!$user->save()) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'error' => 'Registration failed',
                'errors' => $user->errors,
            ];
        }

        $jwt = new JwtHelper();
        $token = $jwt->encode([
            'id' => $user->id,
            'username' => $user->username,
        ]);

        return [
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ];
    }

    public function actionLogin()
    {
        $data = Yii::$app->request->post();

        if (!isset($data['username']) || !isset($data['password'])) {
            throw new BadRequestHttpException('Username and password are required');
        }

        $user = User::findByUsername($data['username']);

        if (!$user || !$user->validatePassword($data['password'])) {
            throw new UnauthorizedHttpException('Invalid username or password');
        }

        $jwt = new JwtHelper();
        $token = $jwt->encode([
            'id' => $user->id,
            'username' => $user->username,
        ]);

        return [
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ];
    }
}