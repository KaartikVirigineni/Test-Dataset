<?php

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Selective\BasePath\BasePathMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addRoutingMiddleware();
$app->add(new BasePathMiddleware($app));
$app->addBodyParsingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Database initialization
$db = new App\Database\Database();

// Middleware
$authMiddleware = new App\Middleware\AuthMiddleware();

// Routes
$app->get('/swagger', function (Request $request, Response $response) {
    $yaml = file_get_contents(__DIR__ . '/../openapi.yaml');
    $response->getBody()->write($yaml);
    return $response->withHeader('Content-Type', 'application/yaml');
});

$app->get('/docs', function (Request $request, Response $response) {
    $html = file_get_contents(__DIR__ . '/../swagger-ui.html');
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->post('/auth/register', function (Request $request, Response $response) use ($db) {
    $data = $request->getParsedBody();
    $controller = new App\Controllers\AuthController($db);
    return $controller->register($request, $response);
});

$app->post('/auth/login', function (Request $request, Response $response) use ($db) {
    $controller = new App\Controllers\AuthController($db);
    return $controller->login($request, $response);
});

$app->get('/users/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $controller = new App\Controllers\UserController($db);
    return $controller->getUser($request, $response, $args);
})->add($authMiddleware);

$app->post('/posts', function (Request $request, Response $response) use ($db) {
    $controller = new App\Controllers\PostController($db);
    return $controller->createPost($request, $response);
})->add($authMiddleware);

$app->get('/posts', function (Request $request, Response $response) use ($db) {
    $controller = new App\Controllers\PostController($db);
    return $controller->getPosts($request, $response);
})->add($authMiddleware);

$app->get('/posts/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $controller = new App\Controllers\PostController($db);
    return $controller->getPost($request, $response, $args);
})->add($authMiddleware);

$app->delete('/posts/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $controller = new App\Controllers\PostController($db);
    return $controller->deletePost($request, $response, $args);
})->add($authMiddleware);

$app->post('/users/{id}/follow', function (Request $request, Response $response, array $args) use ($db) {
    $controller = new App\Controllers\UserController($db);
    return $controller->followUser($request, $response, $args);
})->add($authMiddleware);

$app->delete('/users/{id}/follow', function (Request $request, Response $response, array $args) use ($db) {
    $controller = new App\Controllers\UserController($db);
    return $controller->unfollowUser($request, $response, $args);
})->add($authMiddleware);

$app->get('/users/{id}/followers', function (Request $request, Response $response, array $args) use ($db) {
    $controller = new App\Controllers\UserController($db);
    return $controller->getFollowers($request, $response, $args);
})->add($authMiddleware);

$app->get('/users/{id}/following', function (Request $request, Response $response, array $args) use ($db) {
    $controller = new App\Controllers\UserController($db);
    return $controller->getFollowing($request, $response, $args);
})->add($authMiddleware);

$app->get('/feed', function (Request $request, Response $response) use ($db) {
    $controller = new App\Controllers\PostController($db);
    return $controller->getFeed($request, $response);
})->add($authMiddleware);

$app->run();