<?php

use yii\db\Migration;

class m240101_000002_create_customers_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%customer}}', [
            'id' => $this->primaryKey(),
            'first_name' => $this->string(100)->notNull(),
            'last_name' => $this->string(100)->notNull(),
            'email' => $this->string(255)->notNull(),
            'phone' => $this->string(20),
            'company' => $this->string(255),
            'status' => $this->string(20)->notNull()->defaultValue('active'),
            'notes' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-customer-email', '{{%customer}}', 'email');
        $this->createIndex('idx-customer-status', '{{%customer}}', 'status');
    }

    public function safeDown()
    {
        $this->dropTable('{{%customer}}');
    }
}