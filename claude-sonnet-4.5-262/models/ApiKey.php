<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class ApiKey extends ActiveRecord
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    public static function tableName()
    {
        return 'api_key';
    }

    public function rules()
    {
        return [
            [['user_id', 'name', 'key'], 'required'],
            ['user_id', 'integer'],
            ['name', 'string', 'max' => 255],
            ['key', 'string', 'max' => 64],
            ['key', 'unique'],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['expires_at', 'integer'],
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = time();
                if (empty($this->key)) {
                    $this->key = Yii::$app->security->generateRandomString(32);
                }
            }
            return true;
        }
        return false;
    }

    public function fields()
    {
        return [
            'id',
            'user_id',
            'name',
            'key',
            'status',
            'created_at',
            'expires_at',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function isValid()
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        if ($this->expires_at && $this->expires_at < time()) {
            return false;
        }
        return true;
    }
}