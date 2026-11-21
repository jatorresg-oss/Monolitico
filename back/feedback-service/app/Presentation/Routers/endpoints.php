<?php

use App\Presentation\Repositories\FeedbackRepository;
use Slim\App;

return function (App $app) {
    $app->get('/feedbacks', [FeedbackRepository::class, 'all']);
    $app->get('/feedbacks/stats', [FeedbackRepository::class, 'stats']);
    $app->get('/feedbacks/sprint/{sprintId}', [FeedbackRepository::class, 'bySprint']);
    $app->get('/feedbacks/sprint/{sprintId}/type/{categoria}', [FeedbackRepository::class, 'bySprintAndType']);
    $app->get('/feedbacks/summary/{sprintId}', [FeedbackRepository::class, 'summary']);
    $app->get('/feedbacks/{id}', [FeedbackRepository::class, 'get']);
};
