<?php

namespace App\Presentation\Repositories;

use App\Controllers\ActionTrackingController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class ActionTrackingRepository
{
    public function getPending(Request $request, Response $response, $args): Response
    {
        try {
            $sprintId = $args['sprintId'];
            $controller = new ActionTrackingController();
            $rows = $controller->get_pending_actions($sprintId);
            $response->getBody()->write($rows->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        }
    }

    public function getBySprint(Request $request, Response $response, $args): Response
    {
        try {
            $sprintId = $args['sprintId'];
            $controller = new ActionTrackingController();
            $rows = $controller->get_tracking_by_sprint($sprintId);
            $response->getBody()->write($rows->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        }
    }

    public function getHistory(Request $request, Response $response, $args): Response
    {
        try {
            $feedbackId = $args['feedbackId'];
            $controller = new ActionTrackingController();
            $rows = $controller->get_tracking_history($feedbackId);
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
            $controller = new ActionTrackingController();
            $row = $controller->get($id);
            $response->getBody()->write($row->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['error' => 'Seguimiento no encontrado']));
            return $response->withStatus(404)->withHeader("Content-Type", "application/json");
        }
    }

    public function create(Request $request, Response $response, $args): Response
    {
        try {
            $body = $request->getBody()->getContents();
            $data = json_decode($body, true);
            
            if (empty($data['original_feedback_id']) || empty($data['review_sprint_id']) || empty($data['status'])) {
                $response->getBody()->write(json_encode(['error' => 'Faltan campos obligatorios']));
                return $response->withStatus(400)->withHeader("Content-Type", "application/json");
            }
            
            if (!in_array($data['status'], ['completed', 'partial', 'not_completed', 'carried_over'])) {
                $response->getBody()->write(json_encode(['error' => 'Status inválido']));
                return $response->withStatus(400)->withHeader("Content-Type", "application/json");
            }
            
            $controller = new ActionTrackingController();
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
            
            $controller = new ActionTrackingController();
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
            $controller = new ActionTrackingController();
            $row = $controller->delete($id);
            $response->getBody()->write($row->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(404)->withHeader("Content-Type", "application/json");
        }
    }
}