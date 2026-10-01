<?php

namespace App\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputObjectType;

class Types
{
    private static $user;
    private static $analytic;
    private static $authPayload;
    private static $statsMetric;
    private static $dashboardStats;
    private static $analyticInput;

    public static function user()
    {
        return self::$user ?: (self::$user = new ObjectType([
            'name' => 'User',
            'fields' => [
                'id' => Type::nonNull(Type::int()),
                'email' => Type::nonNull(Type::string()),
                'name' => Type::string(),
            ],
        ]));
    }

    public static function analytic()
    {
        return self::$analytic ?: (self::$analytic = new ObjectType([
            'name' => 'Analytic',
            'fields' => [
                'id' => Type::nonNull(Type::int()),
                'user_id' => Type::nonNull(Type::int()),
                'metric_name' => Type::nonNull(Type::string()),
                'value' => Type::nonNull(Type::float()),
                'metadata' => Type::string(),
                'timestamp' => Type::string(),
            ],
        ]));
    }

    public static function authPayload()
    {
        return self::$authPayload ?: (self::$authPayload = new ObjectType([
            'name' => 'AuthPayload',
            'fields' => [
                'token' => Type::nonNull(Type::string()),
                'user' => Type::nonNull(self::user()),
            ],
        ]));
    }

    public static function statsMetric()
    {
        return self::$statsMetric ?: (self::$statsMetric = new ObjectType([
            'name' => 'StatsMetric',
            'fields' => [
                'metric_name' => Type::nonNull(Type::string()),
                'count' => Type::nonNull(Type::int()),
                'avg_value' => Type::float(),
                'total_value' => Type::float(),
            ],
        ]));
    }

    public static function dashboardStats()
    {
        return self::$dashboardStats ?: (self::$dashboardStats = new ObjectType([
            'name' => 'DashboardStats',
            'fields' => [
                'total_records' => Type::nonNull(Type::int()),
                'metrics' => Type::listOf(self::statsMetric()),
            ],
        ]));
    }

    public static function analyticInput()
    {
        return self::$analyticInput ?: (self::$analyticInput = new InputObjectType([
            'name' => 'AnalyticInput',
            'fields' => [
                'metric_name' => Type::nonNull(Type::string()),
                'value' => Type::nonNull(Type::float()),
                'metadata' => Type::string(),
            ],
        ]));
    }
}