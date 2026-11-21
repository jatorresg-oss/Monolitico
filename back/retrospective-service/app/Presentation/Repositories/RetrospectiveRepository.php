<?php

namespace App\Presentation\Repositories;

use App\Controllers\RetrospectiveController;
use App\Models\Retrospective;
use App\Models\Participant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class RetrospectiveRepository
{
    public function all(Request $request, Response $response, $args): Response
    {
        try {
            $controller = new RetrospectiveController();
            $rows = $controller->get_retrospectives();
            $response->getBody()->write($rows->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (Throwable $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        }
    }

    public function getBySprint(Request $request, Response $response, $args): Response
    {
        try {
            $sprintId = $args['sprintId'];
            $controller = new RetrospectiveController();
            $row = $controller->get_retrospective_by_sprint($sprintId);
            $response->getBody()->write($row->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (ModelNotFoundException $ex) {
            $response->getBody()->write(json_encode(['error' => 'Retrospectiva no encontrada para este sprint']));
            return $response->withStatus(404)->withHeader("Content-Type", "application/json");
        } catch (Throwable $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        }
    }

    public function get(Request $request, Response $response, $args): Response
    {
        try {
            $id = $args['id'];
            $controller = new RetrospectiveController();
            $row = $controller->get_retrospective($id);
            $response->getBody()->write($row->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (ModelNotFoundException $ex) {
            $response->getBody()->write(json_encode(['error' => 'Retrospectiva no encontrada']));
            return $response->withStatus(404)->withHeader("Content-Type", "application/json");
        } catch (Throwable $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        }
    }

    public function create(Request $request, Response $response, $args): Response
    {
        try {
            $body = $request->getBody()->getContents();
            $data = json_decode($body, true);
            
            if (empty($data['sprint_id']) || empty($data['date'])) {
                $response->getBody()->write(json_encode(['error' => 'Faltan campos obligatorios: sprint_id, date']));
                return $response->withStatus(400)->withHeader("Content-Type", "application/json");
            }
            
            $controller = new RetrospectiveController();
            $row = $controller->create($data);
            $response->getBody()->write($row->toJson());
            return $response->withStatus(201)->withHeader("Content-Type", "application/json");
        } catch (Throwable $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(400)->withHeader("Content-Type", "application/json");
        }
    }

    public function update(Request $request, Response $response, $args): Response
    {
        try {
            $id = $args['id'];
            $body = $request->getBody()->getContents();
            $data = json_decode($body, true);
            
            $controller = new RetrospectiveController();
            $row = $controller->update($id, $data);
            $response->getBody()->write($row->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (Throwable $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(404)->withHeader("Content-Type", "application/json");
        }
    }

    public function complete(Request $request, Response $response, $args): Response
    {
        try {
            $id = $args['id'];
            $controller = new RetrospectiveController();
            $row = $controller->complete($id);
            $response->getBody()->write($row->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (Throwable $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(404)->withHeader("Content-Type", "application/json");
        }
    }

    public function delete(Request $request, Response $response, $args): Response
    {
        try {
            $id = $args['id'];
            $controller = new RetrospectiveController();
            $row = $controller->delete($id);
            $response->getBody()->write($row->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (Throwable $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(404)->withHeader("Content-Type", "application/json");
        }
    }
}