<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class Reservation extends ActiveRecord
{
    public static function tableName()
    {
        return 'reservation';
    }

    public function rules()
    {
        return [
            [['user_id', 'table_number', 'guest_name', 'guest_email', 'guest_phone', 'reservation_date', 'reservation_time', 'party_size'], 'required'],
            [['user_id', 'table_number', 'party_size'], 'integer'],
            ['party_size', 'integer', 'min' => 1, 'max' => 20],
            ['table_number', 'integer', 'min' => 1, 'max' => 100],
            [['guest_name', 'guest_email', 'guest_phone', 'reservation_date', 'reservation_time', 'status'], 'string'],
            ['guest_email', 'email'],
            ['status', 'in', 'range' => ['pending', 'confirmed', 'cancelled', 'completed']],
            ['notes', 'string'],
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = time();
                if (!$this->status) {
                    $this->status = 'pending';
                }
            }
            $this->updated_at = time();
            return true;
        }
        return false;
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function fields()
    {
        return [
            'id',
            'table_number',
            'guest_name',
            'guest_email',
            'guest_phone',
            'reservation_date',
            'reservation_time',
            'party_size',
            'status',
            'notes',
            'created_at',
            'updated_at',
        ];
    }
}