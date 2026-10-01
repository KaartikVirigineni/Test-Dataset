<?php

namespace app\models;

use yii\db\ActiveRecord;

class Customer extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%customer}}';
    }

    public function rules()
    {
        return [
            [['first_name', 'last_name', 'email'], 'required'],
            [['first_name', 'last_name'], 'string', 'max' => 100],
            [['email', 'company'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 20],
            [['status'], 'string', 'max' => 20],
            [['notes'], 'string'],
            ['email', 'email'],
            ['status', 'in', 'range' => ['active', 'inactive', 'prospect']],
            ['status', 'default', 'value' => 'active'],
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ],
        ];
    }

    public function fields()
    {
        return [
            'id',
            'first_name',
            'last_name',
            'email',
            'phone',
            'company',
            'status',
            'notes',
            'created_at',
            'updated_at',
        ];
    }
}