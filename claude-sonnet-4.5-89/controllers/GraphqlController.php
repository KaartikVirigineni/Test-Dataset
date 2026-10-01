<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use app\graphql\TypeRegistry;
use app\graphql\QueryType;
use app\graphql\MutationType;

class GraphqlController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'optional' => ['index'],
        ];
        return $behaviors;
    }

    public function actionIndex()
    {
        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            
            $query = $input['query'] ?? null;
            $variables = $input['variables'] ?? null;

            $schema = new Schema([
                'query' => TypeRegistry::query(),
                'mutation' => TypeRegistry::mutation(),
            ]);

            $result = GraphQL::executeQuery($schema, $query, null, null, $variables);
            $output = $result->toArray();
        } catch (\Exception $e) {
            $output = [
                'errors' => [
                    ['message' => $e->getMessage()]
                ]
            ];
        }

        return $output;
    }
}