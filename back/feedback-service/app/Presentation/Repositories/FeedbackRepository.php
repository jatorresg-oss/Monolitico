<?php

namespace App\Presentation\Repositories;

use App\Controllers\FeedbackController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class FeedbackRepository
{
    public function getByRetrospective(Request $request, Response $response, $args): Response
    {
        try {
            $rows = (new FeedbackController())->get_by_retrospective((int) $args['retroId']);
            return $this->json($response, $rows);
        } catch (Throwable $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 500);
        }
    }

    public function getByType(Request $request, Response $response, $args): Response
    {
        try {
            $rows = (new FeedbackController())->get_by_type((int) $args['retroId'], $args['type']);
            return $this->json($response, $rows);
        } catch (InvalidArgumentException $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 400);
        } catch (Throwable $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 500);
        }
    }

    public function get(Request $request, Response $response, $args): Response
    {
        try {
            return $this->json($response, (new FeedbackController())->get_feedback((int) $args['id']));
        } catch (ModelNotFoundException $ex) {
            return $this->json($response, ['error' => 'Ítem no encontrado'], 404);
        } catch (Throwable $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 500);
        }
    }

    public function create(Request $request, Response $response, $args): Response
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->json($response, ['error' => 'El cuerpo debe ser JSON válido.'], 400);
        }

        try {
            $row = (new FeedbackController())->create($data);
            return $this->json($response, $row, 201);
        } catch (InvalidArgumentException $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 400);
        } catch (Throwable $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 400);
        }
    }

    public function createBulk(Request $request, Response $response, $args): Response
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->json($response, ['error' => 'El cuerpo debe ser JSON válido.'], 400);
        }

        try {
            $rows = (new FeedbackController())->create_bulk($data);
            return $this->json($response, $rows, 201);
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
            $row = (new FeedbackController())->update((int) $args['id'], $data);
            return $this->json($response, $row);
        } catch (ModelNotFoundException $ex) {
            return $this->json($response, ['error' => 'Ítem no encontrado'], 404);
        } catch (InvalidArgumentException $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 400);
        } catch (Throwable $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 500);
        }
    }

    public function delete(Request $request, Response $response, $args): Response
    {
        try {
            $row = (new FeedbackController())->delete((int) $args['id']);
            return $this->json($response, $row);
        } catch (ModelNotFoundException $ex) {
            return $this->json($response, ['error' => 'Ítem no encontrado'], 404);
        } catch (Throwable $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 500);
        }
    }

    private function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
