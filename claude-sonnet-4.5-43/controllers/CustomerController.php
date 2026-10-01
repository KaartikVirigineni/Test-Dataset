<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\auth\HttpBearerAuth;
use app\models\Customer;

class CustomerController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];
        return $behaviors;
    }

    public function actionIndex()
    {
        $customers = Customer::find()->all();

        return [
            'success' => true,
            'data' => $customers,
            'count' => count($customers),
        ];
    }

    public function actionView($id)
    {
        $customer = Customer::findOne($id);

        if (!$customer) {
            Yii::$app->response->statusCode = 404;
            return [
                'success' => false,
                'message' => 'Customer not found',
            ];
        }

        return [
            'success' => true,
            'data' => $customer,
        ];
    }

    public function actionCreate()
    {
        $customer = new Customer();
        $customer->load(Yii::$app->request->post(), '');

        if ($customer->save()) {
            Yii::$app->response->statusCode = 201;
            return [
                'success' => true,
                'data' => $customer,
                'message' => 'Customer created successfully',
            ];
        }

        Yii::$app->response->statusCode = 422;
        return [
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $customer->errors,
        ];
    }

    public function actionUpdate($id)
    {
        $customer = Customer::findOne($id);

        if (!$customer) {
            Yii::$app->response->statusCode = 404;
            return [
                'success' => false,
                'message' => 'Customer not found',
            ];
        }

        $customer->load(Yii::$app->request->post(), '');

        if ($customer->save()) {
            return [
                'success' => true,
                'data' => $customer,
                'message' => 'Customer updated successfully',
            ];
        }

        Yii::$app->response->statusCode = 422;
        return [
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $customer->errors,
        ];
    }

    public function actionDelete($id)
    {
        $customer = Customer::findOne($id);

        if (!$customer) {
            Yii::$app->response->statusCode = 404;
            return [
                'success' => false,
                'message' => 'Customer not found',
            ];
        }

        if ($customer->delete()) {
            return [
                'success' => true,
                'message' => 'Customer deleted successfully',
            ];
        }

        Yii::$app->response->statusCode = 500;
        return [
            'success' => false,
            'message' => 'Failed to delete customer',
        ];
    }
}