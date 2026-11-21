<?php

use App\Presentation\Repositories\ActionTrackingRepository;
use Slim\App;

return function (App $app) {
    $repo = new ActionTrackingRepository();
    
    $app->get('/action-tracking/pending/{sprintId}', [$repo, 'getPending']);
    $app->get('/action-tracking/sprint/{sprintId}', [$repo, 'getBySprint']);
    $app->get('/action-tracking/history/{feedbackId}', [$repo, 'getHistory']);
    $app->get('/action-tracking/{id}', [$repo, 'get']);
    $app->post('/action-tracking', [$repo, 'create']);
    $app->put('/action-tracking/{id}', [$repo, 'update']);
    $app->delete('/action-tracking/{id}', [$repo, 'delete']);
};