<?php

namespace App\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use App\Models\AnalyticsModel;

class QueryType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Query',
            'fields' => [
                'analytics' => [
                    'type' => Type::listOf(Types::analytic()),
                    'resolve' => function ($root, $args, $context) {
                        if (!isset($context['user'])) {
                            throw new \Exception('Authentication required');
                        }
                        
                        $model = new AnalyticsModel();
                        return $model->where('user_id', $context['user']['id'])->findAll();
                    },
                ],
                'analytic' => [
                    'type' => Types::analytic(),
                    'args' => [
                        'id' => Type::nonNull(Type::int()),
                    ],
                    'resolve' => function ($root, $args, $context) {
                        if (!isset($context['user'])) {
                            throw new \Exception('Authentication required');
                        }
                        
                        $model = new AnalyticsModel();
                        $analytic = $model
                            ->where('id', $args['id'])
                            ->where('user_id', $context['user']['id'])
                            ->first();
                        
                        if (!$analytic) {
                            throw new \Exception('Analytics record not found');
                        }
                        
                        return $analytic;
                    },
                ],
                'dashboardStats' => [
                    'type' => Types::dashboardStats(),
                    'resolve' => function ($root, $args, $context) {
                        if (!isset($context['user'])) {
                            throw new \Exception('Authentication required');
                        }
                        
                        $userId = $context['user']['id'];
                        $model = new AnalyticsModel();
                        $db = \Config\Database::connect();
                        
                        $total = $model->where('user_id', $userId)->countAllResults();
                        
                        $metrics = $db->query(
                            "SELECT metric_name, COUNT(*) as count, AVG(value) as avg_value, SUM(value) as total_value 
                             FROM analytics 
                             WHERE user_id = ? 
                             GROUP BY metric_name",
                            [$userId]
                        )->getResultArray();
                        
                        return [
                            'total_records' => $total,
                            'metrics' => $metrics,
                        ];
                    },
                ],
            ],
        ]);
    }
}