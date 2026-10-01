<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Route extends ActiveRecord
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    public static function tableName()
    {
        return 'route';
    }

    public function rules()
    {
        return [
            [['path', 'method', 'upstream_url'], 'required'],
            ['path', 'string', 'max' => 255],
            ['method', 'string', 'max' => 10],
            ['method', 'in', 'range' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS']],
            ['upstream_url', 'string', 'max' => 500],
            ['upstream_url', 'url'],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['rate_limit', 'integer', 'min' => 0],
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = time();
            }
            $this->updated_at = time();
            return true;
        }
        return false;
    }

    public function fields()
    {
        return [
            'id',
            'path',
            'method',
            'upstream_url',
            'status',
            'rate_limit',
            'created_at',
            'updated_at',
        ];
    }
}