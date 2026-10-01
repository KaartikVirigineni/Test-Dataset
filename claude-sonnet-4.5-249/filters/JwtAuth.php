<?php

namespace app\filters;

use Yii;
use yii\filters\auth\AuthMethod;
use yii\web\UnauthorizedHttpException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use app\models\User;

class JwtAuth extends AuthMethod
{
    public function authenticate($user, $request, $response)
    {
        $authHeader = $request->getHeaders()->get('Authorization');
        
        if ($authHeader !== null && preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            $token = $matches[1];
            
            try {
                $decoded = JWT::decode($token, new Key(Yii::$app->params['jwtSecret'], 'HS256'));
                
                $identity = User::findIdentity($decoded->sub);
                
                if ($identity) {
                    $user->switchIdentity($identity);
                    return $identity;
                }
            } catch (\Exception $e) {
                throw new UnauthorizedHttpException('Invalid or expired token: ' . $e->getMessage());
            }
        }
        
        return null;
    }

    public function handleFailure($response)
    {
        throw new UnauthorizedHttpException('Authentication required. Please provide a valid Bearer token.');
    }
}