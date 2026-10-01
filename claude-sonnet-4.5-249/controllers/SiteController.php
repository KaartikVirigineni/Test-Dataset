<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\ContentNegotiator;
use yii\web\Response;

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            [
                'class' => ContentNegotiator::class,
                'only' => ['health'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    public function actionSwagger()
    {
        $file = Yii::getAlias('@app/openapi.yaml');
        
        if (!file_exists($file)) {
            throw new \yii\web\NotFoundHttpException('Swagger file not found');
        }

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'application/yaml');
        
        return file_get_contents($file);
    }

    public function actionDocs()
    {
        return $this->renderPartial('docs');
    }

    public function actionHealth()
    {
        return [
            'status' => 'ok',
            'timestamp' => time(),
        ];
    }
}