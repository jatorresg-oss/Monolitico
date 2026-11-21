<?php

use App\Presentation\Repositories\RetroItemRepository;
use Slim\App;

return function (App $app) {
    $app->get('/retro-items', [RetroItemRepository::class, 'all']);
    $app->get('/retro-items/sprint/{sprintId}', [RetroItemRepository::class, 'bySprint']);
    $app->get('/retro-items/sprint/{sprintId}/categoria/{categoria}', [RetroItemRepository::class, 'bySprintAndCategoria']);
    $app->get('/retro-items/{id}', [RetroItemRepository::class, 'get']);
    $app->post('/retro-items', [RetroItemRepository::class, 'create']);
    $app->put('/retro-items/{id}', [RetroItemRepository::class, 'update']);
    $app->delete('/retro-items/{id}', [RetroItemRepository::class, 'delete']);
};
