<?php

namespace App\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use App\Models\ProjectModel;
use App\Models\DocumentationModel;

class QueryType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Query',
            'fields' => [
                'projects' => [
                    'type' => Type::listOf(Types::project()),
                    'resolve' => function() {
                        $model = new ProjectModel();
                        return $model->findAll();
                    }
                ],
                'project' => [
                    'type' => Types::project(),
                    'args' => [
                        'id' => Type::nonNull(Type::int())
                    ],
                    'resolve' => function($root, $args) {
                        $model = new ProjectModel();
                        return $model->find($args['id']);
                    }
                ],
                'documentations' => [
                    'type' => Type::listOf(Types::documentation()),
                    'resolve' => function() {
                        $model = new DocumentationModel();
                        return $model->findAll();
                    }
                ],
                'documentation' => [
                    'type' => Types::documentation(),
                    'args' => [
                        'id' => Type::nonNull(Type::int())
                    ],
                    'resolve' => function($root, $args) {
                        $model = new DocumentationModel();
                        return $model->find($args['id']);
                    }
                ]
            ]
        ]);
    }
}