<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/global/database_config.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

$app = AppFactory::create();

// CORS Middleware
$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// OPTIONS handler
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response;
});

// Health check
$app->get('/health', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['status' => 'ok']));
    return $response->withHeader('Content-Type', 'application/json');
});

// Rutas de ejemplo - AQUÍ AGREGAS TUS ENDPOINTS
$app->get('/sprints', function (Request $request, Response $response) {
    // TODO: Implementar lógica
    $response->getBody()->write(json_encode([]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();