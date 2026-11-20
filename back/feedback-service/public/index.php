<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/global/database_config.php';

$cors = require __DIR__ . '/../app/Middlewares/cors.php';

$app = AppFactory::create();

$cors($app);

$app->get('/health', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['status' => 'ok', 'service' => 'feedback-service']));
    return $response->withHeader('Content-Type', 'application/json');
});

// TODO: Add routes here
// $routes = require __DIR__ . '/../app/Presentation/Routers/routes.php';
// $routes($app);

$app->run();