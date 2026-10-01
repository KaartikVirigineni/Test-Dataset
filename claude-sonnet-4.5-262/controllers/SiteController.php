<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionHealth()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'status' => 'healthy',
            'timestamp' => time(),
        ];
    }

    public function actionSwagger()
    {
        $filePath = Yii::getAlias('@app/openapi.yaml');
        
        if (!file_exists($filePath)) {
            throw new \yii\web\NotFoundHttpException('Swagger file not found');
        }

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/yaml');
        
        return file_get_contents($filePath);
    }

    public function actionDocs()
    {
        $this->layout = false;
        return $this->render('docs');
    }
}