<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class User extends ActiveRecord implements IdentityInterface
{
    const SECRET_KEY = 'your-secret-key-change-in-production';

    public static function tableName()
    {
        return 'user';
    }

    public function rules()
    {
        return [
            [['username', 'email', 'password_hash'], 'required'],
            [['username', 'email'], 'unique'],
            ['email', 'email'],
            ['role', 'in', 'range' => ['admin', 'user']],
        ];
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        try {
            $decoded = JWT::decode($token, new Key(self::SECRET_KEY, 'HS256'));
            return static::findOne(['id' => $decoded->uid]);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->auth_key === $authKey;
    }

    public function validatePassword($password)
    {
        return password_verify($password, $this->password_hash);
    }

    public function generateAccessToken()
    {
        $payload = [
            'uid' => $this->id,
            'username' => $this->username,
            'role' => $this->role,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24 * 7), // 7 days
        ];
        
        $token = JWT::encode($payload, self::SECRET_KEY, 'HS256');
        $this->access_token = $token;
        $this->save(false);
        
        return $token;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->auth_key = Yii::$app->security->generateRandomString();
                $this->created_at = time();
            }
            $this->updated_at = time();
            return true;
        }
        return false;
    }
}