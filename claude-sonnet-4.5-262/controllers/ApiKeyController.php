<?php

namespace app\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use app\models\ApiKey;

class ApiKeyController extends ActiveController
{
    public $modelClass = 'app\models\ApiKey';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];
        return $behaviors;
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        if (!Yii::$app->user->can('manageApiKeys')) {
            if ($action === 'index' || $action === 'view') {
                return;
            }
            throw new ForbiddenHttpException('You are not allowed to perform this action.');
        }
    }

    public function actions()
    {
        $actions = parent::actions();
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
        return $actions;
    }

    public function prepareDataProvider()
    {
        $query = ApiKey::find();
        
        if (!Yii::$app->user->can('manageApiKeys')) {
            $query->andWhere(['user_id' => Yii::$app->user->id]);
        }

        return new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
    }
}