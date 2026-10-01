<?php

use yii\db\Migration;

class m000000_000001_rbac extends Migration
{
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $this->createTable('auth_rule', [
            'name' => $this->string(64)->notNull(),
            'data' => $this->binary(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ]);
        $this->addPrimaryKey('pk-auth_rule', 'auth_rule', 'name');

        $this->createTable('auth_item', [
            'name' => $this->string(64)->notNull(),
            'type' => $this->smallInteger()->notNull(),
            'description' => $this->text(),
            'rule_name' => $this->string(64),
            'data' => $this->binary(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ]);
        $this->addPrimaryKey('pk-auth_item', 'auth_item', 'name');
        $this->createIndex('idx-auth_item-type', 'auth_item', 'type');

        $this->createTable('auth_item_child', [
            'parent' => $this->string(64)->notNull(),
            'child' => $this->string(64)->notNull(),
        ]);
        $this->addPrimaryKey('pk-auth_item_child', 'auth_item_child', ['parent', 'child']);

        $this->createTable('auth_assignment', [
            'item_name' => $this->string(64)->notNull(),
            'user_id' => $this->string(64)->notNull(),
            'created_at' => $this->integer(),
        ]);
        $this->addPrimaryKey('pk-auth_assignment', 'auth_assignment', ['item_name', 'user_id']);

        $this->addForeignKey('fk-auth_item-rule_name', 'auth_item', 'rule_name', 'auth_rule', 'name', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk-auth_item_child-parent', 'auth_item_child', 'parent', 'auth_item', 'name', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-auth_item_child-child', 'auth_item_child', 'child', 'auth_item', 'name', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-auth_assignment-item_name', 'auth_assignment', 'item_name', 'auth_item', 'name', 'CASCADE', 'CASCADE');

        return true;
    }

    public function safeDown()
    {
        $this->dropTable('auth_assignment');
        $this->dropTable('auth_item_child');
        $this->dropTable('auth_item');
        $this->dropTable('auth_rule');

        return true;
    }
}