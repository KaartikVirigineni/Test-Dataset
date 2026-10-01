<?php

use App\Middleware\AuthMiddleware;
use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Initialize database
require __DIR__ . '/../src/Database.php';
App\Database::init();

// Swagger endpoints
$app->get('/swagger', function (Request $request, Response $response) {
    $yaml = file_get_contents(__DIR__ . '/../openapi.yaml');
    $response->getBody()->write($yaml);
    return $response
        ->withHeader('Content-Type', 'application/yaml')
        ->withHeader('Access-Control-Allow-Origin', '*');
});

$app->get('/docs', function (Request $request, Response $response) {
    $html = file_get_contents(__DIR__ . '/../swagger-ui.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

// Public routes
$app->post('/auth/register', '\App\Controllers\AuthController:register');
$app->post('/auth/login', '\App\Controllers\AuthController:login');

// Protected routes
$app->group('', function (RouteCollectorProxy $group) {
    // Recipes
    $group->get('/recipes', '\App\Controllers\RecipeController:list');
    $group->get('/recipes/{id}', '\App\Controllers\RecipeController:get');
    $group->post('/recipes', '\App\Controllers\RecipeController:create');
    $group->put('/recipes/{id}', '\App\Controllers\RecipeController:update');
    $group->delete('/recipes/{id}', '\App\Controllers\RecipeController:delete');
    
    // Users (admin only)
    $group->get('/users', '\App\Controllers\UserController:list');
    $group->get('/users/{id}', '\App\Controllers\UserController:get');
    $group->put('/users/{id}/role', '\App\Controllers\UserController:updateRole');
})->add(new AuthMiddleware($container));

$app->run();