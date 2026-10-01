<?php

namespace app\controllers\api;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use yii\web\ForbiddenHttpException;
use app\filters\JwtAuth;
use app\models\User;

class UserController extends ActiveController
{
    public $modelClass = 'app\models\User';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => JwtAuth::class,
        ];
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['delete']);
        
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
        
        return $actions;
    }

    public function prepareDataProvider()
    {
        if (!Yii::$app->user->can('manageUsers')) {
            throw new ForbiddenHttpException('You do not have permission to view all users.');
        }

        return new \yii\data\ActiveDataProvider([
            'query' => User::find()->where(['status' => User::STATUS_ACTIVE]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        if ($action === 'update' || $action === 'view') {
            if ($model && $model->id !== Yii::$app->user->id && !Yii::$app->user->can('manageUsers')) {
                throw new ForbiddenHttpException('You can only manage your own profile.');
            }
        }
    }
}