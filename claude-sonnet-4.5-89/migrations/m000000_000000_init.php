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
            'auth_key' => $this->string(255),
            'access_token' => $this->string(500),
            'role' => $this->string(50)->defaultValue('user'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createTable('api_route', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'path' => $this->string(500)->notNull(),
            'method' => $this->string(10)->notNull(),
            'target_url' => $this->string(1000)->notNull(),
            'enabled' => $this->boolean()->defaultValue(true),
            'rate_limit' => $this->integer()->defaultValue(100),
            'created_by' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createTable('request_log', [
            'id' => $this->primaryKey(),
            'route_id' => $this->integer(),
            'user_id' => $this->integer(),
            'method' => $this->string(10)->notNull(),
            'path' => $this->string(500)->notNull(),
            'status_code' => $this->integer(),
            'response_time' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
        ]);

        // Insert default admin user (password: admin123)
        $this->insert('user', [
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'auth_key' => Yii::$app->security->generateRandomString(),
            'role' => 'admin',
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        // Insert sample routes
        $this->insert('api_route', [
            'name' => 'Example API',
            'path' => '/api/external/example',
            'method' => 'GET',
            'target_url' => 'https://jsonplaceholder.typicode.com/posts',
            'enabled' => true,
            'rate_limit' => 100,
            'created_by' => 1,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('request_log');
        $this->dropTable('api_route');
        $this->dropTable('user');
    }
}