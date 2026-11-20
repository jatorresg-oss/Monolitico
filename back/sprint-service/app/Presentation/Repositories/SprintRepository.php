<?php

namespace App\Presentation\Repositories;

use App\Controllers\SprintController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class SprintRepository
{
    public function all(Request $request, Response $response, $args): Response
    {
        try {
            $controller = new SprintController();
            $rows = $controller->get_sprints();
            $response->getBody()->write($rows->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        }
    }

    public function get(Request $request, Response $response, $args): Response
    {
        try {
            $id = $args['id'];
            $controller = new SprintController();
            $row = $controller->get_sprint($id);
            $response->getBody()->write($row->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['error' => 'Sprint no encontrado']));
            return $response->withStatus(404)->withHeader("Content-Type", "application/json");
        }
    }

    public function getByTeam(Request $request, Response $response, $args): Response
    {
        try {
            $teamId = $args['teamId'];
            $controller = new SprintController();
            $rows = $controller->get_sprints_by_team($teamId);
            $response->getBody()->write($rows->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        }
    }

    public function create(Request $request, Response $response, $args): Response
    {
        try {
            $body = $request->getBody()->getContents();
            $data = json_decode($body, true);
            
            if (empty($data['team_id']) || empty($data['name']) || empty($data['start_date']) || empty($data['end_date'])) {
                $response->getBody()->write(json_encode(['error' => 'Faltan campos obligatorios']));
                return $response->withStatus(400)->withHeader("Content-Type", "application/json");
            }
            
            $controller = new SprintController();
            $row = $controller->create($data);
            $response->getBody()->write($row->toJson());
            return $response->withStatus(201)->withHeader("Content-Type", "application/json");
        } catch (Exception $ex) {
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
            
            $controller = new SprintController();
            $row = $controller->update($id, $data);
            $response->getBody()->write($row->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(404)->withHeader("Content-Type", "application/json");
        }
    }

    public function delete(Request $request, Response $response, $args): Response
    {
        try {
            $id = $args['id'];
            $controller = new SprintController();
            $row = $controller->delete($id);
            $response->getBody()->write($row->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(404)->withHeader("Content-Type", "application/json");
        }
    }
}