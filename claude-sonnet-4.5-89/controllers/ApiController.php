<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use app\models\ApiRoute;
use app\models\User;
use app\models\RequestLog;

class ApiController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];
        return $behaviors;
    }

    public function actionRoutes()
    {
        $routes = ApiRoute::find()->all();
        return array_map(function($route) {
            return [
                'id' => $route->id,
                'name' => $route->name,
                'path' => $route->path,
                'method' => $route->method,
                'target_url' => $route->target_url,
                'enabled' => (bool)$route->enabled,
                'rate_limit' => $route->rate_limit,
                'created_at' => $route->created_at,
            ];
        }, $routes);
    }

    public function actionCreateRoute()
    {
        if (!$this->checkAdmin()) {
            Yii::$app->response->statusCode = 403;
            return ['error' => 'Admin access required'];
        }

        $request = Yii::$app->request->post();
        
        $route = new ApiRoute();
        $route->name = $request['name'] ?? null;
        $route->path = $request['path'] ?? null;
        $route->method = $request['method'] ?? 'GET';
        $route->target_url = $request['target_url'] ?? null;
        $route->enabled = $request['enabled'] ?? true;
        $route->rate_limit = $request['rate_limit'] ?? 100;

        if ($route->save()) {
            return [
                'id' => $route->id,
                'name' => $route->name,
                'path' => $route->path,
                'method' => $route->method,
                'target_url' => $route->target_url,
                'enabled' => (bool)$route->enabled,
                'rate_limit' => $route->rate_limit,
            ];
        }

        Yii::$app->response->statusCode = 400;
        return ['errors' => $route->errors];
    }

    public function actionUpdateRoute($id)
    {
        if (!$this->checkAdmin()) {
            Yii::$app->response->statusCode = 403;
            return ['error' => 'Admin access required'];
        }

        $route = ApiRoute::findOne($id);
        if (!$route) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'Route not found'];
        }

        $request = Yii::$app->request->getBodyParams();
        
        $route->name = $request['name'] ?? $route->name;
        $route->path = $request['path'] ?? $route->path;
        $route->method = $request['method'] ?? $route->method;
        $route->target_url = $request['target_url'] ?? $route->target_url;
        $route->enabled = $request['enabled'] ?? $route->enabled;
        $route->rate_limit = $request['rate_limit'] ?? $route->rate_limit;

        if ($route->save()) {
            return [
                'id' => $route->id,
                'name' => $route->name,
                'path' => $route->path,
                'method' => $route->method,
                'target_url' => $route->target_url,
                'enabled' => (bool)$route->enabled,
                'rate_limit' => $route->rate_limit,
            ];
        }

        Yii::$app->response->statusCode = 400;
        return ['errors' => $route->errors];
    }

    public function actionDeleteRoute($id)
    {
        if (!$this->checkAdmin()) {
            Yii::$app->response->statusCode = 403;
            return ['error' => 'Admin access required'];
        }

        $route = ApiRoute::findOne($id);
        if (!$route) {
            Yii::$app->response->statusCode = 404;
            return ['error' => 'Route not found'];
        }

        $route->delete();
        return ['success' => true];
    }

    public function actionUsers()
    {
        if (!$this->checkAdmin()) {
            Yii::$app->response->statusCode = 403;
            return ['error' => 'Admin access required'];
        }

        $users = User::find()->all();
        return array_map(function($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
            ];
        }, $users);
    }

    public function actionLogs()
    {
        if (!$this->checkAdmin()) {
            Yii::$app->response->statusCode = 403;
            return ['error' => 'Admin access required'];
        }

        $logs = RequestLog::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(100)
            ->all();
            
        return array_map(function($log) {
            return [
                'id' => $log->id,
                'route_id' => $log->route_id,
                'user_id' => $log->user_id,
                'method' => $log->method,
                'path' => $log->path,
                'status_code' => $log->status_code,
                'response_time' => $log->response_time,
                'created_at' => $log->created_at,
            ];
        }, $logs);
    }

    private function checkAdmin()
    {
        $user = Yii::$app->user->identity;
        return $user && $user->role === 'admin';
    }
}