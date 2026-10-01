<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

class SiteController extends Controller
{
    public function actionSwagger()
    {
        $swaggerFile = Yii::getAlias('@app/openapi.yaml');
        
        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/yaml');
        
        return file_get_contents($swaggerFile);
    }

    public function actionSwaggerUi()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
        
        return $this->renderPartial('swagger-ui');
    }
}