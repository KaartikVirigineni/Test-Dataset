<?php

namespace app\controllers\api;

use Yii;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use app\filters\JwtAuth;
use app\models\Presence;

class PresenceController extends ActiveController
{
    public $modelClass = 'app\models\Presence';

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
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
        unset($actions['delete']);
        return $actions;
    }

    public function prepareDataProvider()
    {
        if (!Yii::$app->user->can('viewPresence')) {
            throw new ForbiddenHttpException('You do not have permission to view presence data.');
        }

        return new ActiveDataProvider([
            'query' => Presence::find()->with('user')->orderBy(['last_seen' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);
    }

    public function checkAccess($action, $model = null, $params = [])
    {
        if (in_array($action, ['update', 'create'])) {
            if (!Yii::$app->user->can('updatePresence')) {
                throw new ForbiddenHttpException('You do not have permission to update presence.');
            }
            
            if ($model && $action === 'update') {
                if ($model->user_id !== Yii::$app->user->id && !Yii::$app->user->can('manageUsers')) {
                    throw new ForbiddenHttpException('You can only update your own presence.');
                }
            }
        }

        if ($action === 'view') {
            if (!Yii::$app->user->can('viewPresence')) {
                throw new ForbiddenHttpException('You do not have permission to view presence.');
            }
        }
    }

    protected function verbs()
    {
        return [
            'index' => ['GET', 'HEAD'],
            'view' => ['GET', 'HEAD'],
            'create' => ['POST'],
            'update' => ['PUT', 'PATCH'],
            'delete' => [],
        ];
    }
}