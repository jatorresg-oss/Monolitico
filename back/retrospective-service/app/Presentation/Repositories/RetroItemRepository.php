<?php

namespace App\Presentation\Repositories;

use App\Controllers\RetroItemController;
use App\Models\RetroItem;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class RetroItemRepository
{
    public function all(Request $request, Response $response, $args): Response
    {
        try {
            $params = $request->getQueryParams();
            $sprintId = isset($params['sprint_id']) && $params['sprint_id'] !== ''
                ? (int) $params['sprint_id']
                : null;
            $categoria = $this->normalizeCategoria($params['categoria'] ?? null);
            if ($categoria === false) {
                return $this->error($response, 'Categoría no válida.', 422);
            }

            $controller = new RetroItemController();
            return $this->json($response, $controller->get_items($sprintId, $categoria));
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    public function bySprint(Request $request, Response $response, $args): Response
    {
        try {
            $this->ensureSprint((int) $args['sprintId']);
            $controller = new RetroItemController();
            return $this->json($response, $controller->get_items_by_sprint((int) $args['sprintId']));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Sprint no encontrado', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    public function bySprintAndCategoria(Request $request, Response $response, $args): Response
    {
        try {
            $categoria = $this->normalizeCategoria($args['categoria'] ?? null);
            if ($categoria === false) {
                return $this->error($response, 'Categoría no válida.', 422);
            }

            $this->ensureSprint((int) $args['sprintId']);
            $controller = new RetroItemController();
            $items = $controller->get_items((int) $args['sprintId'], $categoria);
            return $this->json($response, $items);
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Sprint no encontrado', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    public function get(Request $request, Response $response, $args): Response
    {
        try {
            $controller = new RetroItemController();
            return $this->json($response, $controller->get_item((int) $args['id']));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Item de retrospectiva no encontrado', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    public function create(Request $request, Response $response, $args): Response
    {
        try {
            $data = $this->body($request);
            $errors = $this->validate($data);
            if ($errors) {
                return $this->error($response, implode(' ', $errors), 422);
            }

            $controller = new RetroItemController();
            return $this->json($response, $controller->create($data), 201);
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Sprint no encontrado', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 400);
        }
    }

    public function update(Request $request, Response $response, $args): Response
    {
        try {
            $id = (int) $args['id'];
            $data = $this->body($request);
            $current = RetroItem::findOrFail($id);
            $merged = array_merge($current->toArray(), $data);
            $errors = $this->validate($merged, true);
            if ($errors) {
                return $this->error($response, implode(' ', $errors), 422);
            }

            $controller = new RetroItemController();
            return $this->json($response, $controller->update($id, $data));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Item de retrospectiva o sprint no encontrado', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 400);
        }
    }

    public function delete(Request $request, Response $response, $args): Response
    {
        try {
            $controller = new RetroItemController();
            return $this->json($response, $controller->delete((int) $args['id']));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Item de retrospectiva no encontrado', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    private function ensureSprint(int $sprintId): void
    {
        Sprint::findOrFail($sprintId);
    }

    private function body(Request $request): array
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('El cuerpo de la petición debe ser JSON válido.');
        }
        return $data;
    }

    private function validate(array $data, bool $partial = false): array
    {
        $errors = [];

        if (!$partial || array_key_exists('sprint_id', $data)) {
            if (empty($data['sprint_id']) || !filter_var($data['sprint_id'], FILTER_VALIDATE_INT)) {
                $errors[] = 'El sprint es obligatorio.';
            }
        }

        if (!$partial || array_key_exists('categoria', $data)) {
            $categoria = $this->normalizeCategoria($data['categoria'] ?? null);
            if ($categoria === false || $categoria === null) {
                $errors[] = 'La categoría debe ser: accion, logro, impedimento, comentario u otro.';
            }
        }

        if (!$partial || array_key_exists('descripcion', $data)) {
            if (!isset($data['descripcion']) || trim((string) $data['descripcion']) === '') {
                $errors[] = 'La descripción es obligatoria.';
            }
        }

        $categoria = $this->normalizeCategoria($data['categoria'] ?? null);
        if ($categoria === 'accion' && !empty($data['fecha_revision']) && !$this->isDate((string) $data['fecha_revision'])) {
            $errors[] = 'La fecha de revisión no es válida (formato AAAA-MM-DD).';
        }

        return $errors;
    }

    private function normalizeCategoria($categoria)
    {
        if ($categoria === null || $categoria === '') {
            return null;
        }
        return in_array($categoria, RetroItemController::CATEGORIAS, true) ? $categoria : false;
    }

    private function isDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
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
