<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

class SiteController extends Controller
{
    public function actionSwagger()
    {
        $yamlPath = Yii::getAlias('@app/openapi.yaml');
        
        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/x-yaml');
        
        return file_get_contents($yamlPath);
    }

    public function actionDocs()
    {
        $this->layout = false;
        return $this->render('docs');
    }
}