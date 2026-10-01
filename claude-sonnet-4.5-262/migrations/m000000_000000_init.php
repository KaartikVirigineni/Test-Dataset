<?php

use yii\db\Migration;

class m000000_000000_init extends Migration
{
    public function safeUp()
    {
        $this->createTable('user', [
            'id' => $this->primaryKey(),
            'username' => $this->string(255)->notNull()->unique(),
            'email' => $this->string(255)->notNull()->unique(),
            'password_hash' => $this->string(255)->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'status' => $this->smallInteger()->notNull()->defaultValue(10),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createTable('api_key', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'name' => $this->string(255)->notNull(),
            'key' => $this->string(64)->notNull()->unique(),
            'status' => $this->smallInteger()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->notNull(),
            'expires_at' => $this->integer(),
        ]);

        $this->createTable('route', [
            'id' => $this->primaryKey(),
            'path' => $this->string(255)->notNull(),
            'method' => $this->string(10)->notNull(),
            'upstream_url' => $this->string(500)->notNull(),
            'status' => $this->smallInteger()->notNull()->defaultValue(1),
            'rate_limit' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-api_key-user_id',
            'api_key',
            'user_id',
            'user',
            'id',
            'CASCADE'
        );

        $passwordHash = Yii::$app->security->generatePasswordHash('admin123');
        $authKey = Yii::$app->security->generateRandomString();
        
        $this->insert('user', [
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password_hash' => $passwordHash,
            'auth_key' => $authKey,
            'status' => 10,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        return true;
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-api_key-user_id', 'api_key');
        $this->dropTable('route');
        $this->dropTable('api_key');
        $this->dropTable('user');

        return true;
    }
}