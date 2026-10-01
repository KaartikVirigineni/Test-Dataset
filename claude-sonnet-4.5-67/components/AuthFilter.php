<?php

namespace app\components;

use Yii;
use yii\base\ActionFilter;
use yii\web\UnauthorizedHttpException;

class AuthFilter extends ActionFilter
{
    public function beforeAction($action)
    {
        $authHeader = Yii::$app->request->headers->get('Authorization');
        
        if (!$authHeader || !preg_match('/^Bearer\s+(.*)$/i', $authHeader, $matches)) {
            throw new UnauthorizedHttpException('Missing or invalid authorization header');
        }

        $token = $matches[1];
        $identity = Yii::$app->user->identityClass::findIdentityByAccessToken($token);

        if (!$identity) {
            throw new UnauthorizedHttpException('Invalid or expired token');
        }

        Yii::$app->user->setIdentity($identity);
        return parent::beforeAction($action);
    }
}