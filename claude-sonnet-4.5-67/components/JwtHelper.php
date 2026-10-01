<?php

namespace app\components;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Yii;

class JwtHelper
{
    private $secret;

    public function __construct()
    {
        $this->secret = Yii::$app->params['jwtSecret'];
    }

    public function encode($data)
    {
        $payload = [
            'id' => $data['id'],
            'username' => $data['username'],
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24),
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function decode($token)
    {
        try {
            return JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }
}