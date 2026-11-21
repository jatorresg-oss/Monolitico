<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/global/database_config.php';

$cors = require __DIR__ . '/../app/Middlewares/cors.php';
$routes = require __DIR__ . '/../app/Presentation/Routers/routes.php';

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$cors($app);

$routes($app);

$app->get('/health', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['status' => 'ok', 'service' => 'sprint-service'], JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
});

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler(function (Request $request, Throwable $exception) use ($app) {
    $status = $exception->getCode();
    if ($status < 400 || $status > 599) {
        $status = 500;
    }

    $response = $app->getResponseFactory()->createResponse($status);
    $response->getBody()->write(json_encode([
        'error' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE));

    return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
});

$app->run();