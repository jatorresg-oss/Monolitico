<?php

namespace App\Presentation\Repositories;

use App\Controllers\FeedbackController;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class FeedbackRepository
{
    public function all(Request $request, Response $response, $args): Response
    {
        try {
            $controller = new FeedbackController();
            return $this->json($response, $controller->get_all_feedback());
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    public function bySprint(Request $request, Response $response, $args): Response
    {
        try {
            $sprintId = (int) $args['sprintId'];
            $this->ensureSprint($sprintId);
            $controller = new FeedbackController();
            return $this->json($response, $controller->get_feedback_by_sprint($sprintId));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Sprint no encontrado', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    public function bySprintAndType(Request $request, Response $response, $args): Response
    {
        try {
            $categoria = $args['categoria'] ?? null;
            if (!in_array($categoria, FeedbackController::CATEGORIAS, true)) {
                return $this->error($response, 'Categoría no válida.', 422);
            }

            $sprintId = (int) $args['sprintId'];
            $this->ensureSprint($sprintId);
            $controller = new FeedbackController();
            return $this->json($response, $controller->get_feedback_by_sprint($sprintId, $categoria));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Sprint no encontrado', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    public function summary(Request $request, Response $response, $args): Response
    {
        try {
            $controller = new FeedbackController();
            return $this->json($response, $controller->get_summary_by_sprint((int) $args['sprintId']));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Sprint no encontrado', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    public function stats(Request $request, Response $response, $args): Response
    {
        try {
            $controller = new FeedbackController();
            return $this->json($response, $controller->get_global_summary());
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    public function get(Request $request, Response $response, $args): Response
    {
        try {
            $controller = new FeedbackController();
            return $this->json($response, $controller->get_feedback((int) $args['id']));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Feedback no encontrado', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    private function ensureSprint(int $sprintId): void
    {
        Sprint::findOrFail($sprintId);
    }

    private function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    private function error(Response $response, string $message, int $status): Response
    {
        return $this->json($response, ['error' => $message], $status);
    }
}
