<?php

use yii\db\Migration;

class m000000_000000_init extends Migration
{
    public function safeUp()
    {
        $this->createTable('user', [
            'id' => $this->primaryKey(),
            'username' => $this->string(255)->notNull()->unique(),
            'password_hash' => $this->string(255)->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createTable('reservation', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'table_number' => $this->integer()->notNull(),
            'guest_name' => $this->string(255)->notNull(),
            'guest_email' => $this->string(255)->notNull(),
            'guest_phone' => $this->string(50)->notNull(),
            'reservation_date' => $this->string(50)->notNull(),
            'reservation_time' => $this->string(50)->notNull(),
            'party_size' => $this->integer()->notNull(),
            'status' => $this->string(50)->notNull()->defaultValue('pending'),
            'notes' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-reservation-user_id', 'reservation', 'user_id');
        $this->createIndex('idx-reservation-status', 'reservation', 'status');
        $this->createIndex('idx-reservation-date', 'reservation', 'reservation_date');

        $passwordHash = Yii::$app->security->generatePasswordHash('password123');
        $authKey = Yii::$app->security->generateRandomString();
        $timestamp = time();

        $this->insert('user', [
            'username' => 'admin',
            'password_hash' => $passwordHash,
            'auth_key' => $authKey,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('reservation');
        $this->dropTable('user');
    }
}