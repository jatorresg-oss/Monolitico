<?php

namespace App\Presentation\Repositories;

use App\Controllers\ActionTrackingController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class ActionTrackingRepository
{
    public function getPending(Request $request, Response $response, $args): Response
    {
        try {
            $rows = (new ActionTrackingController())->get_pending_actions((int) $args['sprintId']);
            return $this->json($response, $rows);
        } catch (ModelNotFoundException $ex) {
            return $this->json($response, ['error' => 'Sprint no encontrado'], 404);
        } catch (Throwable $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 500);
        }
    }

    public function getBySprint(Request $request, Response $response, $args): Response
    {
        try {
            $rows = (new ActionTrackingController())->get_tracking_by_sprint((int) $args['sprintId']);
            return $this->json($response, $rows);
        } catch (Throwable $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 500);
        }
    }

    public function getHistory(Request $request, Response $response, $args): Response
    {
        try {
            $rows = (new ActionTrackingController())->get_tracking_history((int) $args['feedbackId']);
            return $this->json($response, $rows);
        } catch (Throwable $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 500);
        }
    }

    public function get(Request $request, Response $response, $args): Response
    {
        try {
            $row = (new ActionTrackingController())->get((int) $args['id']);
            return $this->json($response, $row);
        } catch (ModelNotFoundException $ex) {
            return $this->json($response, ['error' => 'Acción no encontrada'], 404);
        } catch (Throwable $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 500);
        }
    }

    public function create(Request $request, Response $response, $args): Response
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)
            || empty($data['original_feedback_id'])
            || empty($data['review_sprint_id'])
            || !isset($data['status'])
            || !in_array($data['status'], ['completed', 'partial', 'not_completed', 'carried_over'], true)
        ) {
            return $this->json($response, ['error' => 'Los datos de seguimiento no son válidos.'], 400);
        }

        try {
            $row = (new ActionTrackingController())->create($data);
            return $this->json($response, $row, 201);
        } catch (ModelNotFoundException $ex) {
            return $this->json($response, ['error' => 'Acción no encontrada'], 404);
        } catch (Throwable $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 400);
        }
    }

    public function update(Request $request, Response $response, $args): Response
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->json($response, ['error' => 'El cuerpo debe ser JSON válido.'], 400);
        }

        try {
            $row = (new ActionTrackingController())->update((int) $args['id'], $data);
            return $this->json($response, $row);
        } catch (ModelNotFoundException $ex) {
            return $this->json($response, ['error' => 'Acción no encontrada'], 404);
        } catch (InvalidArgumentException $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 400);
        } catch (Throwable $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 500);
        }
    }

    public function delete(Request $request, Response $response, $args): Response
    {
        return $this->json($response, [
            'error' => 'El esquema retro_items no almacena un historial de seguimiento independiente.',
        ], 501);
    }

    private function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
