<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\UnauthorizedHttpException;
use yii\web\BadRequestHttpException;
use app\models\User;

class AuthController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']['except'] = ['login', 'register'];
        return $behaviors;
    }

    public function actionLogin()
    {
        $username = Yii::$app->request->post('username');
        $password = Yii::$app->request->post('password');

        if (!$username || !$password) {
            throw new BadRequestHttpException('Username and password are required');
        }

        $user = User::findByUsername($username);
        if (!$user || !$user->validatePassword($password)) {
            throw new UnauthorizedHttpException('Invalid username or password');
        }

        return [
            'success' => true,
            'data' => [
                'token' => $user->generateAccessToken(),
                'user' => $user,
            ],
        ];
    }

    public function actionRegister()
    {
        $user = new User();
        $user->username = Yii::$app->request->post('username');
        $user->email = Yii::$app->request->post('email');
        $password = Yii::$app->request->post('password');

        if (!$user->username || !$user->email || !$password) {
            throw new BadRequestHttpException('Username, email, and password are required');
        }

        $user->setPassword($password);

        if (!$user->save()) {
            throw new BadRequestHttpException(json_encode($user->errors));
        }

        $auth = Yii::$app->authManager;
        $userRole = $auth->getRole('user');
        if ($userRole) {
            $auth->assign($userRole, $user->id);
        }

        return [
            'success' => true,
            'data' => [
                'token' => $user->generateAccessToken(),
                'user' => $user,
            ],
        ];
    }
}