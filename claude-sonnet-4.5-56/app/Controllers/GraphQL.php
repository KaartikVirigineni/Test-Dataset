<?php

namespace App\Controllers;

use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use App\GraphQL\Types;
use App\GraphQL\QueryType;
use App\GraphQL\MutationType;
use App\Libraries\JWTHelper;

class GraphQL extends BaseController
{
    public function index()
    {
        try {
            $rawInput = $this->request->getBody();
            
            if ($this->request->getMethod() === 'get') {
                $query = $this->request->getGet('query');
                $variables = $this->request->getGet('variables');
                if ($variables) {
                    $variables = json_decode($variables, true);
                }
            } else {
                $input = json_decode($rawInput, true);
                $query = $input['query'] ?? null;
                $variables = $input['variables'] ?? null;
            }

            $authHeader = $this->request->getHeaderLine('Authorization');
            $user = null;

            if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
                $token = substr($authHeader, 7);
                $jwtHelper = new JWTHelper();
                try {
                    $user = $jwtHelper->validateToken($token);
                } catch (\Exception $e) {
                    // User remains null
                }
            }

            $context = ['user' => $user];

            $schema = new Schema(
                (new SchemaConfig())
                    ->setQuery(new QueryType())
                    ->setMutation(new MutationType())
            );

            $result = GraphQLBase::executeQuery($schema, $query, null, $context, $variables);
            $output = $result->toArray();

        } catch (\Exception $e) {
            $output = [
                'errors' => [
                    ['message' => $e->getMessage()]
                ]
            ];
        }

        return $this->jsonResponse($output);
    }
}