<?php

use yii\db\Migration;

class m000000_000002_init_rbac extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $manageUsers = $auth->createPermission('manageUsers');
        $manageUsers->description = 'Manage users';
        $auth->add($manageUsers);

        $manageApiKeys = $auth->createPermission('manageApiKeys');
        $manageApiKeys->description = 'Manage API keys';
        $auth->add($manageApiKeys);

        $manageRoutes = $auth->createPermission('manageRoutes');
        $manageRoutes->description = 'Manage routes';
        $auth->add($manageRoutes);

        $manageRoles = $auth->createPermission('manageRoles');
        $manageRoles->description = 'Manage roles';
        $auth->add($manageRoles);

        $admin = $auth->createRole('admin');
        $admin->description = 'Administrator';
        $auth->add($admin);
        $auth->addChild($admin, $manageUsers);
        $auth->addChild($admin, $manageApiKeys);
        $auth->addChild($admin, $manageRoutes);
        $auth->addChild($admin, $manageRoles);

        $user = $auth->createRole('user');
        $user->description = 'Regular user';
        $auth->add($user);
        $auth->addChild($user, $manageApiKeys);

        $auth->assign($admin, 1);

        return true;
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $auth->removeAll();
        return true;
    }
}