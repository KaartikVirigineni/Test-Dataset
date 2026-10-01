<?php

namespace app\commands;

use Yii;
use yii\console\Controller;

class RbacController extends Controller
{
    public function actionInit()
    {
        $auth = Yii::$app->authManager;
        $auth->removeAll();

        $viewPresence = $auth->createPermission('viewPresence');
        $viewPresence->description = 'View presence status';
        $auth->add($viewPresence);

        $updatePresence = $auth->createPermission('updatePresence');
        $updatePresence->description = 'Update own presence status';
        $auth->add($updatePresence);

        $manageUsers = $auth->createPermission('manageUsers');
        $manageUsers->description = 'Manage all users';
        $auth->add($manageUsers);

        $user = $auth->createRole('user');
        $user->description = 'Regular user';
        $auth->add($user);
        $auth->addChild($user, $viewPresence);
        $auth->addChild($user, $updatePresence);

        $admin = $auth->createRole('admin');
        $admin->description = 'Administrator';
        $auth->add($admin);
        $auth->addChild($admin, $user);
        $auth->addChild($admin, $manageUsers);

        echo "RBAC initialized successfully.\n";
    }
}