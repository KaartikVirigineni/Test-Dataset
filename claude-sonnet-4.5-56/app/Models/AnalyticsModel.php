<?php

namespace App\Models;

use CodeIgniter\Model;

class AnalyticsModel extends Model
{
    protected $table = 'analytics';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['user_id', 'metric_name', 'value', 'metadata', 'timestamp'];

    protected bool $allowEmptyInserts = false;

    protected $useTimestamps = false;

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $afterFind = ['afterFind'];

    protected function afterFind(array $data)
    {
        if (isset($data['data'])) {
            foreach ($data['data'] as &$row) {
                if (isset($row['metadata'])) {
                    $row['metadata'] = json_decode($row['metadata'], true);
                }
            }
        } elseif (isset($data['metadata'])) {
            $data['metadata'] = json_decode($data['metadata'], true);
        }

        return $data;
    }
}