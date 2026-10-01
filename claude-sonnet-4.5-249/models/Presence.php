<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class Presence extends ActiveRecord
{
    const STATUS_ONLINE = 'online';
    const STATUS_AWAY = 'away';
    const STATUS_BUSY = 'busy';
    const STATUS_OFFLINE = 'offline';

    public static function tableName()
    {
        return '{{%presence}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['user_id', 'last_seen'], 'required'],
            [['user_id', 'last_seen', 'created_at', 'updated_at'], 'integer'],
            ['status', 'string', 'max' => 50],
            ['status', 'in', 'range' => [self::STATUS_ONLINE, self::STATUS_AWAY, self::STATUS_BUSY, self::STATUS_OFFLINE]],
            ['device_info', 'string'],
        ];
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields['user'] = function ($model) {
            return $model->user ? [
                'id' => $model->user->id,
                'username' => $model->user->username,
                'email' => $model->user->email,
            ] : null;
        };
        return $fields;
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->last_seen = time();
            return true;
        }
        return false;
    }
}