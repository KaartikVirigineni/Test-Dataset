<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAnalyticsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INTEGER',
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INTEGER',
            ],
            'metric_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'value' => [
                'type' => 'REAL',
            ],
            'metadata' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'timestamp' => [
                'type' => 'DATETIME',
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('analytics');
    }

    public function down()
    {
        $this->forge->dropTable('analytics');
    }
}