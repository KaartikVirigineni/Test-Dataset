<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MockSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'user_id' => 1,
                'name' => 'Sample GET User',
                'endpoint' => '/api/users/1',
                'method' => 'GET',
                'response_body' => json_encode(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']),
                'status_code' => 200,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => 1,
                'name' => 'Sample POST Create',
                'endpoint' => '/api/items',
                'method' => 'POST',
                'response_body' => json_encode(['id' => 1, 'message' => 'Item created successfully']),
                'status_code' => 201,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('mocks')->insertBatch($data);
    }
}