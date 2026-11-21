<?php

namespace App\Presentation\Repositories;

use App\Controllers\SprintController;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class SprintRepository
{
    public function all(Request $request, Response $response, $args): Response
    {
        try {
            $controller = new SprintController();
            return $this->json($response, $controller->get_sprints());
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    public function get(Request $request, Response $response, $args): Response
    {
        try {
            $controller = new SprintController();
            return $this->json($response, $controller->get_sprint((int) $args['id']));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Sprint no encontrado', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
        }
    }

    public function create(Request $request, Response $response, $args): Response
    {
        try {
            $data = $this->normalize($this->body($request));
            $errors = $this->validate($data);
            if ($errors) {
                return $this->error($response, implode(' ', $errors), 422);
            }

            $controller = new SprintController();
            return $this->json($response, $controller->create($data), 201);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 400);
        }
    }

    public function update(Request $request, Response $response, $args): Response
    {
        try {
            $id = (int) $args['id'];
            $data = $this->normalize($this->body($request));
            $current = Sprint::findOrFail($id);
            $merged = array_merge($current->toArray(), $data);
            $errors = $this->validate($merged, true);
            if ($errors) {
                return $this->error($response, implode(' ', $errors), 422);
            }

            $controller = new SprintController();
            return $this->json($response, $controller->update($id, $data));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Sprint no encontrado', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 400);
        }
    }

    public function delete(Request $request, Response $response, $args): Response
    {
        try {
            $controller = new SprintController();
            return $this->json($response, $controller->delete((int) $args['id']));
        } catch (ModelNotFoundException $ex) {
            return $this->error($response, 'Sprint no encontrado', 404);
        } catch (Throwable $ex) {
            return $this->error($response, $ex->getMessage(), 500);
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

    private function validate(array $data, bool $partial = false): array
    {
        $errors = [];

        if (!$partial || array_key_exists('nombre', $data)) {
            if (!isset($data['nombre']) || trim((string) $data['nombre']) === '') {
                $errors[] = 'El nombre es obligatorio.';
            } elseif (mb_strlen(trim((string) $data['nombre'])) > 100) {
                $errors[] = 'El nombre no puede superar los 100 caracteres.';
            }
        }

        foreach (['fecha_inicio', 'fecha_fin'] as $field) {
            if (!$partial || array_key_exists($field, $data)) {
                if (empty($data[$field]) || !$this->isDate((string) $data[$field])) {
                    $errors[] = 'La fecha de ' . ($field === 'fecha_inicio' ? 'inicio' : 'fin') . ' no es válida (formato AAAA-MM-DD).';
                }
            }
        }

        if (empty($errors) && !empty($data['fecha_inicio']) && !empty($data['fecha_fin'])) {
            if ($data['fecha_inicio'] > $data['fecha_fin']) {
                $errors[] = 'La fecha de inicio no puede ser posterior a la fecha de fin.';
            }
        }

        return $errors;
    }

    private function normalize(array $data): array
    {
        foreach ([
            'nombre' => 'name',
            'fecha_inicio' => 'start_date',
            'fecha_fin' => 'end_date',
        ] as $field => $legacyField) {
            if (!array_key_exists($field, $data) && array_key_exists($legacyField, $data)) {
                $data[$field] = $data[$legacyField];
            }
            unset($data[$legacyField]);
        }

        return $data;
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
