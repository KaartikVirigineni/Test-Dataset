<?php

namespace app\graphql;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class TypeRegistry
{
    private static $query;
    private static $mutation;
    private static $userType;
    private static $apiRouteType;
    private static $requestLogType;

    public static function query()
    {
        return self::$query ?: (self::$query = new QueryType());
    }

    public static function mutation()
    {
        return self::$mutation ?: (self::$mutation = new MutationType());
    }

    public static function userType()
    {
        return self::$userType ?: (self::$userType = new ObjectType([
            'name' => 'User',
            'fields' => [
                'id' => Type::int(),
                'username' => Type::string(),
                'email' => Type::string(),
                'role' => Type::string(),
                'created_at' => Type::int(),
            ],
        ]));
    }

    public static function apiRouteType()
    {
        return self::$apiRouteType ?: (self::$apiRouteType = new ObjectType([
            'name' => 'ApiRoute',
            'fields' => [
                'id' => Type::int(),
                'name' => Type::string(),
                'path' => Type::string(),
                'method' => Type::string(),
                'target_url' => Type::string(),
                'enabled' => Type::boolean(),
                'rate_limit' => Type::int(),
                'created_at' => Type::int(),
            ],
        ]));
    }

    public static function requestLogType()
    {
        return self::$requestLogType ?: (self::$requestLogType = new ObjectType([
            'name' => 'RequestLog',
            'fields' => [
                'id' => Type::int(),
                'route_id' => Type::int(),
                'user_id' => Type::int(),
                'method' => Type::string(),
                'path' => Type::string(),
                'status_code' => Type::int(),
                'response_time' => Type::int(),
                'created_at' => Type::int(),
            ],
        ]));
    }
}