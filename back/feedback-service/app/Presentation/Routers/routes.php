<?php

use App\Presentation\Repositories\FeedbackRepository;
use Slim\App;

return function (App $app) {
    $repo = new FeedbackRepository();
    
    $app->get('/feedbacks/retrospective/{retroId}', [$repo, 'getByRetrospective']);
    $app->get('/feedbacks/retrospective/{retroId}/type/{type}', [$repo, 'getByType']);
    $app->get('/feedbacks/{id}', [$repo, 'get']);
    $app->post('/feedbacks', [$repo, 'create']);
    $app->post('/feedbacks/bulk', [$repo, 'createBulk']);
    $app->put('/feedbacks/{id}', [$repo, 'update']);
    $app->delete('/feedbacks/{id}', [$repo, 'delete']);
};