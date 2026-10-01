<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use yii\web\ForbiddenHttpException;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class RoleController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];
        return $behaviors;
    }

    public function beforeAction($action)
    {
        if (!Yii::$app->user->can('manageRoles')) {
            throw new ForbiddenHttpException('You are not allowed to perform this action.');
        }
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $auth = Yii::$app->authManager;
        $roles = $auth->getRoles();
        $permissions = $auth->getPermissions();

        $result = [
            'roles' => [],
            'permissions' => [],
        ];

        foreach ($roles as $name => $role) {
            $result['roles'][] = [
                'name' => $name,
                'description' => $role->description,
                'created_at' => $role->createdAt,
            ];
        }

        foreach ($permissions as $name => $permission) {
            $result['permissions'][] = [
                'name' => $name,
                'description' => $permission->description,
                'created_at' => $permission->createdAt,
            ];
        }

        return [
            'success' => true,
            'data' => $result,
        ];
    }

    public function actionCreate()
    {
        $auth = Yii::$app->authManager;
        
        $name = Yii::$app->request->post('name');
        $description = Yii::$app->request->post('description', '');
        $type = Yii::$app->request->post('type', 'role');

        if (!$name) {
            throw new BadRequestHttpException('Name is required');
        }

        if ($type === 'role') {
            $item = $auth->createRole($name);
        } else {
            $item = $auth->createPermission($name);
        }

        $item->description = $description;
        $auth->add($item);

        return [
            'success' => true,
            'data' => [
                'name' => $name,
                'description' => $description,
                'type' => $type,
            ],
        ];
    }

    public function actionDelete($name)
    {
        $auth = Yii::$app->authManager;
        $item = $auth->getRole($name) ?: $auth->getPermission($name);

        if (!$item) {
            throw new NotFoundHttpException('Role or permission not found');
        }

        $auth->remove($item);

        return [
            'success' => true,
            'message' => 'Item deleted successfully',
        ];
    }

    public function actionAssign()
    {
        $auth = Yii::$app->authManager;
        
        $roleName = Yii::$app->request->post('role');
        $userId = Yii::$app->request->post('user_id');

        if (!$roleName || !$userId) {
            throw new BadRequestHttpException('Role and user_id are required');
        }

        $role = $auth->getRole($roleName);
        if (!$role) {
            throw new NotFoundHttpException('Role not found');
        }

        $auth->assign($role, $userId);

        return [
            'success' => true,
            'message' => 'Role assigned successfully',
        ];
    }

    public function actionRevoke()
    {
        $auth = Yii::$app->authManager;
        
        $roleName = Yii::$app->request->post('role');
        $userId = Yii::$app->request->post('user_id');

        if (!$roleName || !$userId) {
            throw new BadRequestHttpException('Role and user_id are required');
        }

        $role = $auth->getRole($roleName);
        if (!$role) {
            throw new NotFoundHttpException('Role not found');
        }

        $auth->revoke($role, $userId);

        return [
            'success' => true,
            'message' => 'Role revoked successfully',
        ];
    }
}