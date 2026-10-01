<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');
$routes->get('/swagger', 'Swagger::index');
$routes->get('/docs', 'Swagger::ui');

$routes->group('api', ['namespace' => 'App\Controllers\Api'], function($routes) {
    $routes->post('auth/register', 'AuthController::register');
    $routes->post('auth/login', 'AuthController::login');
    
    $routes->group('', ['filter' => 'auth'], function($routes) {
        $routes->get('auth/me', 'AuthController::me');
        
        $routes->get('mocks', 'MockController::index');
        $routes->get('mocks/(:num)', 'MockController::show/$1');
        $routes->post('mocks', 'MockController::create');
        $routes->put('mocks/(:num)', 'MockController::update/$1');
        $routes->delete('mocks/(:num)', 'MockController::delete/$1');
    });
});