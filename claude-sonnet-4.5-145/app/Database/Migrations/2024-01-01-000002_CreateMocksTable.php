<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMocksTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INTEGER',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INTEGER',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'endpoint' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'method' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
            ],
            'response_body' => [
                'type' => 'TEXT',
            ],
            'status_code' => [
                'type' => 'INTEGER',
                'constraint' => 3,
                'default' => 200,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('mocks');
    }

    public function down()
    {
        $this->forge->dropTable('mocks');
    }
}