<?php

use App\Presentation\Repositories\RetrospectiveRepository;
use Slim\App;

return function (App $app) {
    $repo = new RetrospectiveRepository();
    
    $app->get('/retrospectives', [$repo, 'all']);
    $app->get('/retrospectives/sprint/{sprintId}', [$repo, 'getBySprint']);
    $app->get('/retrospectives/{id}', [$repo, 'get']);
    $app->post('/retrospectives', [$repo, 'create']);
    $app->put('/retrospectives/{id}', [$repo, 'update']);
    $app->put('/retrospectives/{id}/complete', [$repo, 'complete']);
    $app->delete('/retrospectives/{id}', [$repo, 'delete']);
};