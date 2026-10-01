<?php

use yii\db\Migration;

class m240101_000002_create_presence_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%presence}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'status' => $this->string(50)->notNull()->defaultValue('offline'),
            'last_seen' => $this->integer()->notNull(),
            'device_info' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-presence-user_id', '{{%presence}}', 'user_id');
        $this->createIndex('idx-presence-status', '{{%presence}}', 'status');
        $this->addForeignKey(
            'fk-presence-user_id',
            '{{%presence}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-presence-user_id', '{{%presence}}');
        $this->dropTable('{{%presence}}');
    }
}