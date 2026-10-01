<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use app\models\Reservation;
use app\components\AuthFilter;

class ReservationController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => AuthFilter::class,
        ];
        return $behaviors;
    }

    public function actionIndex()
    {
        $userId = Yii::$app->user->id;
        $status = Yii::$app->request->get('status');

        $query = Reservation::find()->where(['user_id' => $userId]);

        if ($status) {
            $query->andWhere(['status' => $status]);
        }

        $reservations = $query->orderBy(['reservation_date' => SORT_DESC, 'reservation_time' => SORT_DESC])
            ->all();

        return $reservations;
    }

    public function actionView($id)
    {
        $reservation = $this->findModel($id);
        return $reservation;
    }

    public function actionCreate()
    {
        $data = Yii::$app->request->post();
        
        $reservation = new Reservation();
        $reservation->user_id = Yii::$app->user->id;
        $reservation->load($data, '');

        if (!$reservation->save()) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'error' => 'Failed to create reservation',
                'errors' => $reservation->errors,
            ];
        }

        Yii::$app->response->statusCode = 201;
        return $reservation;
    }

    public function actionUpdate($id)
    {
        $reservation = $this->findModel($id);
        $data = Yii::$app->request->bodyParams;

        $reservation->load($data, '');

        if (!$reservation->save()) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'error' => 'Failed to update reservation',
                'errors' => $reservation->errors,
            ];
        }

        return $reservation;
    }

    public function actionDelete($id)
    {
        $reservation = $this->findModel($id);
        
        if ($reservation->delete()) {
            Yii::$app->response->statusCode = 204;
            return null;
        }

        Yii::$app->response->statusCode = 400;
        return [
            'success' => false,
            'error' => 'Failed to delete reservation',
        ];
    }

    protected function findModel($id)
    {
        $reservation = Reservation::findOne([
            'id' => $id,
            'user_id' => Yii::$app->user->id,
        ]);

        if ($reservation === null) {
            throw new NotFoundHttpException('Reservation not found');
        }

        return $reservation;
    }
}