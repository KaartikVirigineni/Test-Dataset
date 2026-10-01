<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');
$routes->get('/swagger', 'Home::swagger');
$routes->get('/docs', 'Home::swaggerUI');

$routes->group('api', function($routes) {
    $routes->post('auth/register', 'AuthController::register');
    $routes->post('auth/login', 'AuthController::login');
    
    $routes->group('', ['filter' => 'auth'], function($routes) {
        $routes->get('projects', 'ProjectController::index');
        $routes->get('projects/(:num)', 'ProjectController::show/$1');
        $routes->post('projects', 'ProjectController::create');
        $routes->put('projects/(:num)', 'ProjectController::update/$1');
        $routes->delete('projects/(:num)', 'ProjectController::delete/$1');
        
        $routes->get('docs', 'DocumentationController::index');
        $routes->get('docs/(:num)', 'DocumentationController::show/$1');
        $routes->post('docs', 'DocumentationController::create');
        $routes->put('docs/(:num)', 'DocumentationController::update/$1');
        $routes->delete('docs/(:num)', 'DocumentationController::delete/$1');
    });
});

$routes->post('graphql', 'GraphQLController::handle');