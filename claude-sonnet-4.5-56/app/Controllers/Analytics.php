<?php

namespace App\Controllers;

use App\Models\AnalyticsModel;

class Analytics extends BaseController
{
    protected $analyticsModel;

    public function __construct()
    {
        $this->analyticsModel = new AnalyticsModel();
    }

    public function index()
    {
        $userId = $this->request->user['id'];
        $analytics = $this->analyticsModel->where('user_id', $userId)->findAll();
        
        return $this->jsonResponse($analytics);
    }

    public function show($id)
    {
        $userId = $this->request->user['id'];
        $analytic = $this->analyticsModel
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$analytic) {
            return $this->errorResponse('Analytics record not found', 404);
        }

        return $this->jsonResponse($analytic);
    }

    public function create()
    {
        $json = $this->request->getJSON();
        $userId = $this->request->user['id'];

        if (!isset($json->metric_name) || !isset($json->value)) {
            return $this->errorResponse('metric_name and value are required', 400);
        }

        $data = [
            'user_id' => $userId,
            'metric_name' => $json->metric_name,
            'value' => $json->value,
            'metadata' => isset($json->metadata) ? json_encode($json->metadata) : null,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $id = $this->analyticsModel->insert($data);
        $analytic = $this->analyticsModel->find($id);

        return $this->jsonResponse($analytic, 201);
    }

    public function update($id)
    {
        $json = $this->request->getJSON();
        $userId = $this->request->user['id'];

        $analytic = $this->analyticsModel
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$analytic) {
            return $this->errorResponse('Analytics record not found', 404);
        }

        $data = [];
        if (isset($json->metric_name)) $data['metric_name'] = $json->metric_name;
        if (isset($json->value)) $data['value'] = $json->value;
        if (isset($json->metadata)) $data['metadata'] = json_encode($json->metadata);

        $this->analyticsModel->update($id, $data);
        $updated = $this->analyticsModel->find($id);

        return $this->jsonResponse($updated);
    }

    public function delete($id)
    {
        $userId = $this->request->user['id'];

        $analytic = $this->analyticsModel
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$analytic) {
            return $this->errorResponse('Analytics record not found', 404);
        }

        $this->analyticsModel->delete($id);

        return $this->jsonResponse(['message' => 'Deleted successfully']);
    }

    public function stats()
    {
        $userId = $this->request->user['id'];
        
        $db = \Config\Database::connect();
        
        $total = $this->analyticsModel->where('user_id', $userId)->countAllResults();
        
        $metrics = $db->query(
            "SELECT metric_name, COUNT(*) as count, AVG(value) as avg_value, SUM(value) as total_value 
             FROM analytics 
             WHERE user_id = ? 
             GROUP BY metric_name",
            [$userId]
        )->getResultArray();

        return $this->jsonResponse([
            'total_records' => $total,
            'metrics' => $metrics,
        ]);
    }
}