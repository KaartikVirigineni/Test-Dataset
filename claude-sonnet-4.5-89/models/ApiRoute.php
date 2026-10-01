<?php

namespace app\models;

use yii\db\ActiveRecord;

class ApiRoute extends ActiveRecord
{
    public static function tableName()
    {
        return 'api_route';
    }

    public function rules()
    {
        return [
            [['name', 'path', 'method', 'target_url'], 'required'],
            ['enabled', 'boolean'],
            ['rate_limit', 'integer', 'min' => 1],
            ['method', 'in', 'range' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']],
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = time();
                $this->created_by = \Yii::$app->user->id ?? null;
            }
            $this->updated_at = time();
            return true;
        }
        return false;
    }
}