<?php

namespace App\Presentation\Repositories;

use App\Controllers\RetrospectiveController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class RetrospectiveRepository
{
    public function all(Request $request, Response $response, $args): Response
    {
        try {
            return $this->json($response, (new RetrospectiveController())->get_retrospectives());
        } catch (Throwable $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 500);
        }
    }

    public function getBySprint(Request $request, Response $response, $args): Response
    {
        try {
            $row = (new RetrospectiveController())->get_retrospective_by_sprint((int) $args['sprintId']);
            return $this->json($response, $row);
        } catch (ModelNotFoundException $ex) {
            return $this->json($response, ['error' => 'Retrospectiva no encontrada para este sprint'], 404);
        } catch (Throwable $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 500);
        }
    }

    public function get(Request $request, Response $response, $args): Response
    {
        try {
            $row = (new RetrospectiveController())->get_retrospective((int) $args['id']);
            return $this->json($response, $row);
        } catch (ModelNotFoundException $ex) {
            return $this->json($response, ['error' => 'Retrospectiva no encontrada'], 404);
        } catch (Throwable $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 500);
        }
    }

    public function create(Request $request, Response $response, $args): Response
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data) || empty($data['sprint_id'])) {
            return $this->json($response, ['error' => 'El campo sprint_id es obligatorio'], 400);
        }

        try {
            $row = (new RetrospectiveController())->create($data);
            return $this->json($response, $row, 201);
        } catch (ModelNotFoundException $ex) {
            return $this->json($response, ['error' => 'Sprint no encontrado'], 404);
        } catch (Throwable $ex) {
            return $this->json($response, ['error' => $ex->getMessage()], 400);
        }
    }

    public function update(Request $request, Response $response, $args): Response
    {
        return $this->json($response, [
            'error' => 'El esquema registro_retro_db no almacena estado ni notas de retrospectiva.',
        ], 501);
    }

    public function complete(Request $request, Response $response, $args): Response
    {
        return $this->update($request, $response, $args);
    }

    public function delete(Request $request, Response $response, $args): Response
    {
        try {
            $row = (new RetrospectiveController())->delete((int) $args['id']);
            return $this->json($response, $row);
        } catch (ModelNotFoundException $ex) {
            return $this->json($response, ['error' => 'Sprint no encontrado'], 404);
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
