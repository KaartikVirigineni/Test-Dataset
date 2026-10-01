<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    public function actionSwagger()
    {
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/yaml');
        
        $swaggerPath = Yii::getAlias('@app/openapi.yaml');
        return file_get_contents($swaggerPath);
    }

    public function actionDocs()
    {
        return $this->renderPartial('docs');
    }

    public function actionError()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $exception = Yii::$app->errorHandler->exception;
        
        if ($exception !== null) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ];
        }
        
        return [
            'success' => false,
            'message' => 'An error occurred',
        ];
    }
}