<?php

use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes): void {
    $routes->setRouteClass('DashedRoute');

    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Health', 'action' => 'index']);
        $builder->connect('/health', ['controller' => 'Health', 'action' => 'index']);
        $builder->connect('/swagger', ['controller' => 'Swagger', 'action' => 'spec']);
        $builder->connect('/swagger-ui', ['controller' => 'Swagger', 'action' => 'ui']);
        
        $builder->scope('/api', function (RouteBuilder $api): void {
            $api->setExtensions(['json']);
            
            $api->connect('/auth/login', ['controller' => 'Auth', 'action' => 'login'])
                ->setMethods(['POST']);
            
            $api->resources('Flags', [
                'only' => ['index', 'view', 'add', 'edit', 'delete']
            ]);
            
            $api->connect('/flags/:id/toggle', ['controller' => 'Flags', 'action' => 'toggle'])
                ->setMethods(['POST'])
                ->setPass(['id']);
            
            $api->connect('/config/:key', ['controller' => 'Flags', 'action' => 'getByKey'])
                ->setMethods(['GET'])
                ->setPass(['key']);
        });
    });
};