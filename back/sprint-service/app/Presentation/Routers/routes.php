<?php

use App\Presentation\Repositories\SprintRepository;
use Slim\App;

return function (App $app) {
    $repo = new SprintRepository();
    
    $app->get('/sprints', [$repo, 'all']);
    $app->get('/sprints/team/{teamId}', [$repo, 'getByTeam']);
    $app->get('/sprints/{id}', [$repo, 'get']);
    $app->post('/sprints', [$repo, 'create']);
    $app->put('/sprints/{id}', [$repo, 'update']);
    $app->delete('/sprints/{id}', [$repo, 'delete']);
};