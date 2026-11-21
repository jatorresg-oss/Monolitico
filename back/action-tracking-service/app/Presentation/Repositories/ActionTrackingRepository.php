<?php

namespace App\Presentation\Repositories;

use App\Controllers\ActionTrackingController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class ActionTrackingRepository
{
    public function bySprint(Request $request, Response $response, $args): Response
    {
        try {
            $controller = new ActionTrackingController();
            return $this->json($response, $controller->get_actions_by_sprint((int) $args['sprintId']));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Sprint no encontrado', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    public function pending(Request $request, Response $response, $args): Response
    {
        try {
            $controller = new ActionTrackingController();
            return $this->json($response, $controller->get_pending_actions((int) $args['sprintId']));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Sprint no encontrado', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    public function stats(Request $request, Response $response, $args): Response
    {
        try {
            $sprintId = isset($args['sprintId']) ? (int) $args['sprintId'] : null;
            $controller = new ActionTrackingController();
            return $this->json($response, $controller->get_stats($sprintId));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Sprint no encontrado', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    public function get(Request $request, Response $response, $args): Response
    {
        try {
            $controller = new ActionTrackingController();
            return $this->json($response, $controller->get_action((int) $args['id']));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Acción no encontrada', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    public function history(Request $request, Response $response, $args): Response
    {
        try {
            $controller = new ActionTrackingController();
            return $this->json($response, $controller->get_history((int) $args['id']));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Acción no encontrada', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    public function update(Request $request, Response $response, $args): Response
    {
        try {
            $data = $this->body($request);
            $errors = $this->validate($data);
            if ($errors) {
                return $this->error($response, implode(' ', $errors), 422);
            }

            if (array_key_exists('cumplida', $data)) {
                $data['cumplida'] = $this->toBool($data['cumplida']);
            }

            $controller = new ActionTrackingController();
            return $this->json($response, $controller->update((int) $args['id'], $data));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Acción no encontrada', 404);
        } catch (\InvalidArgumentException $ex) {
            return $this->error($response, $ex->getMessage(), 422);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 400);
        }
    }

    private function body(Request $request): array
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('El cuerpo de la petición debe ser JSON válido.');
        }
        return $data;
    }

    private function validate(array $data): array
    {
        $errors = [];

        if (array_key_exists('descripcion', $data) && trim((string) $data['descripcion']) === '') {
            $errors[] = 'La descripción no puede estar vacía.';
        }

        if (!empty($data['fecha_revision'])) {
            $date = \DateTime::createFromFormat('Y-m-d', (string) $data['fecha_revision']);
            if ($date === false || $date->format('Y-m-d') !== $data['fecha_revision']) {
                $errors[] = 'La fecha de revisión no es válida (formato AAAA-MM-DD).';
            }
        }

        if (array_key_exists('cumplida', $data) && $data['cumplida'] !== null) {
            $normalized = filter_var($data['cumplida'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($normalized === null) {
                $errors[] = 'El campo cumplida debe ser verdadero o falso.';
            }
        }

        return $errors;
    }

    private function toBool($value): ?bool
    {
        if ($value === null) {
            return null;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
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
