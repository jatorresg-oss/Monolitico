<?php

namespace App\Presentation\Repositories;

use App\Controllers\FeedbackController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class FeedbackRepository
{
    public function getByRetrospective(Request $request, Response $response, $args): Response
    {
        try {
            $retroId = $args['retroId'];
            $controller = new FeedbackController();
            $rows = $controller->get_by_retrospective($retroId);
            $response->getBody()->write($rows->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        }
    }

    public function getByType(Request $request, Response $response, $args): Response
    {
        try {
            $retroId = $args['retroId'];
            $type = $args['type'];
            
            if (!in_array($type, ['positive', 'negative', 'action', 'comment'])) {
                $response->getBody()->write(json_encode(['error' => 'Tipo inválido. Use: positive, negative, action, comment']));
                return $response->withStatus(400)->withHeader("Content-Type", "application/json");
            }
            
            $controller = new FeedbackController();
            $rows = $controller->get_by_type($retroId, $type);
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
            $controller = new FeedbackController();
            $row = $controller->get_feedback($id);
            $response->getBody()->write($row->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['error' => 'Ítem no encontrado']));
            return $response->withStatus(404)->withHeader("Content-Type", "application/json");
        }
    }

    public function create(Request $request, Response $response, $args): Response
    {
        try {
            $body = $request->getBody()->getContents();
            $data = json_decode($body, true);
            
            if (empty($data['retrospective_id']) || empty($data['type']) || empty($data['title'])) {
                $response->getBody()->write(json_encode(['error' => 'Faltan campos obligatorios: retrospective_id, type, title']));
                return $response->withStatus(400)->withHeader("Content-Type", "application/json");
            }
            
            if (!in_array($data['type'], ['positive', 'negative', 'action', 'comment'])) {
                $response->getBody()->write(json_encode(['error' => 'Tipo inválido. Use: positive, negative, action, comment']));
                return $response->withStatus(400)->withHeader("Content-Type", "application/json");
            }
            
            $controller = new FeedbackController();
            $row = $controller->create($data);
            $response->getBody()->write($row->toJson());
            return $response->withStatus(201)->withHeader("Content-Type", "application/json");
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(400)->withHeader("Content-Type", "application/json");
        }
    }

    public function createBulk(Request $request, Response $response, $args): Response
    {
        try {
            $body = $request->getBody()->getContents();
            $data = json_decode($body, true);
            
            if (empty($data['retrospective_id']) || empty($data['items']) || !is_array($data['items'])) {
                $response->getBody()->write(json_encode(['error' => 'Faltan campos obligatorios: retrospective_id, items (array)']));
                return $response->withStatus(400)->withHeader("Content-Type", "application/json");
            }
            
            foreach ($data['items'] as $item) {
                if (empty($item['type']) || empty($item['title']) || !in_array($item['type'], ['positive', 'negative', 'action', 'comment'])) {
                    $response->getBody()->write(json_encode(['error' => 'Cada item debe tener type (positive/negative/action/comment) y title']));
                    return $response->withStatus(400)->withHeader("Content-Type", "application/json");
                }
            }
            
            $controller = new FeedbackController();
            $rows = $controller->create_bulk($data);
            $response->getBody()->write(json_encode($rows));
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
            
            $controller = new FeedbackController();
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
            $controller = new FeedbackController();
            $row = $controller->delete($id);
            $response->getBody()->write($row->toJson());
            return $response->withHeader("Content-Type", "application/json");
        } catch (Exception $ex) {
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()]));
            return $response->withStatus(404)->withHeader("Content-Type", "application/json");
        }
    }
}