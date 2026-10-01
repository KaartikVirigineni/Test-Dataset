<?php

use yii\db\Migration;

class m240101_000003_create_rbac_tables extends Migration
{
    public function safeUp()
    {
        $authManager = Yii::$app->authManager;
        $tableOptions = null;

        $this->createTable($authManager->ruleTable, [
            'name' => $this->string(64)->notNull(),
            'data' => $this->binary(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
            'PRIMARY KEY (name)',
        ], $tableOptions);

        $this->createTable($authManager->itemTable, [
            'name' => $this->string(64)->notNull(),
            'type' => $this->smallInteger()->notNull(),
            'description' => $this->text(),
            'rule_name' => $this->string(64),
            'data' => $this->binary(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
            'PRIMARY KEY (name)',
        ], $tableOptions);
        $this->createIndex('idx-auth_item-type', $authManager->itemTable, 'type');

        $this->createTable($authManager->itemChildTable, [
            'parent' => $this->string(64)->notNull(),
            'child' => $this->string(64)->notNull(),
            'PRIMARY KEY (parent, child)',
        ], $tableOptions);

        $this->createTable($authManager->assignmentTable, [
            'item_name' => $this->string(64)->notNull(),
            'user_id' => $this->string(64)->notNull(),
            'created_at' => $this->integer(),
            'PRIMARY KEY (item_name, user_id)',
        ], $tableOptions);

        $this->addForeignKey('fk-auth_item-rule_name', $authManager->itemTable, 'rule_name', $authManager->ruleTable, 'name', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk-auth_item_child-parent', $authManager->itemChildTable, 'parent', $authManager->itemTable, 'name', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-auth_item_child-child', $authManager->itemChildTable, 'child', $authManager->itemTable, 'name', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-auth_assignment-item_name', $authManager->assignmentTable, 'item_name', $authManager->itemTable, 'name', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $authManager = Yii::$app->authManager;
        
        $this->dropTable($authManager->assignmentTable);
        $this->dropTable($authManager->itemChildTable);
        $this->dropTable($authManager->itemTable);
        $this->dropTable($authManager->ruleTable);
    }
}