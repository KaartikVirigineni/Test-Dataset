<?php

namespace App\Controllers;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use App\GraphQL\Types;
use App\GraphQL\QueryType;
use App\GraphQL\MutationType;

class GraphQLController extends BaseController
{
    public function handle()
    {
        try {
            $schema = new Schema([
                'query' => new QueryType(),
                'mutation' => new MutationType()
            ]);

            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            
            $query = $input['query'] ?? '';
            $variables = $input['variables'] ?? null;

            $result = GraphQL::executeQuery($schema, $query, null, null, $variables);
            $output = $result->toArray();

            return $this->response->setJSON($output);
        } catch (\Exception $e) {
            return $this->response
                ->setJSON(['errors' => [['message' => $e->getMessage()]]])
                ->setStatusCode(400);
        }
    }
}