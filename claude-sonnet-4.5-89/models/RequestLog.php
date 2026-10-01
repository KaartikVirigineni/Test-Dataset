<?php

namespace app\models;

use yii\db\ActiveRecord;

class RequestLog extends ActiveRecord
{
    public static function tableName()
    {
        return 'request_log';
    }

    public function rules()
    {
        return [
            [['method', 'path', 'created_at'], 'required'],
            [['route_id', 'user_id', 'status_code', 'response_time'], 'integer'],
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = time();
            }
            return true;
        }
        return false;
    }

    public static function logRequest($routeId, $userId, $method, $path, $statusCode, $responseTime)
    {
        $log = new self();
        $log->route_id = $routeId;
        $log->user_id = $userId;
        $log->method = $method;
        $log->path = $path;
        $log->status_code = $statusCode;
        $log->response_time = $responseTime;
        $log->save();
    }
}