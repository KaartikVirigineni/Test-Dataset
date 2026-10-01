<?php

namespace App\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputObjectType;

class Types
{
    private static $user;
    private static $project;
    private static $documentation;
    private static $authPayload;

    public static function user(): ObjectType
    {
        if (!self::$user) {
            self::$user = new ObjectType([
                'name' => 'User',
                'fields' => [
                    'id' => Type::int(),
                    'email' => Type::string(),
                    'name' => Type::string(),
                    'created_at' => Type::string()
                ]
            ]);
        }
        return self::$user;
    }

    public static function project(): ObjectType
    {
        if (!self::$project) {
            self::$project = new ObjectType([
                'name' => 'Project',
                'fields' => [
                    'id' => Type::int(),
                    'name' => Type::string(),
                    'description' => Type::string(),
                    'repository_url' => Type::string(),
                    'status' => Type::string(),
                    'created_at' => Type::string(),
                    'updated_at' => Type::string()
                ]
            ]);
        }
        return self::$project;
    }

    public static function documentation(): ObjectType
    {
        if (!self::$documentation) {
            self::$documentation = new ObjectType([
                'name' => 'Documentation',
                'fields' => [
                    'id' => Type::int(),
                    'title' => Type::string(),
                    'content' => Type::string(),
                    'project_id' => Type::int(),
                    'category' => Type::string(),
                    'created_at' => Type::string(),
                    'updated_at' => Type::string()
                ]
            ]);
        }
        return self::$documentation;
    }

    public static function authPayload(): ObjectType
    {
        if (!self::$authPayload) {
            self::$authPayload = new ObjectType([
                'name' => 'AuthPayload',
                'fields' => [
                    'token' => Type::string(),
                    'user' => self::user()
                ]
            ]);
        }
        return self::$authPayload;
    }
}