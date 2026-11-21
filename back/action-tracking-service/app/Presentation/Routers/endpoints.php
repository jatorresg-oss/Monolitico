<?php

use App\Presentation\Repositories\ActionTrackingRepository;
use Slim\App;

return function (App $app) {
    $app->get('/action-tracking/stats', [ActionTrackingRepository::class, 'stats']);
    $app->get('/action-tracking/stats/{sprintId}', [ActionTrackingRepository::class, 'stats']);
    $app->get('/action-tracking/pending/{sprintId}', [ActionTrackingRepository::class, 'pending']);
    $app->get('/action-tracking/sprint/{sprintId}', [ActionTrackingRepository::class, 'bySprint']);
    $app->get('/action-tracking/history/{id}', [ActionTrackingRepository::class, 'history']);
    $app->get('/action-tracking/{id}', [ActionTrackingRepository::class, 'get']);
    $app->map(['PUT', 'PATCH'], '/action-tracking/{id}', [ActionTrackingRepository::class, 'update']);
};
