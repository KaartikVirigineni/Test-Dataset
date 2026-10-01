<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');

// Swagger
$routes->get('swagger', 'Swagger::index');
$routes->get('swagger-ui', 'Swagger::ui');

// Auth routes
$routes->post('api/auth/register', 'Auth::register');
$routes->post('api/auth/login', 'Auth::login');

// Analytics routes (protected)
$routes->group('api', ['filter' => 'auth'], function($routes) {
    $routes->get('analytics', 'Analytics::index');
    $routes->get('analytics/(:num)', 'Analytics::show/$1');
    $routes->post('analytics', 'Analytics::create');
    $routes->put('analytics/(:num)', 'Analytics::update/$1');
    $routes->delete('analytics/(:num)', 'Analytics::delete/$1');
    
    $routes->get('dashboard/stats', 'Analytics::stats');
});

// GraphQL endpoint
$routes->post('graphql', 'GraphQL::index');
$routes->get('graphql', 'GraphQL::index');