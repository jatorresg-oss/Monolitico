<?php

namespace App\Controllers;

use App\Models\FeedbackItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class FeedbackController
{
    private const TYPE_TO_CATEGORY = [
        'positive' => 'logro',
        'negative' => 'impedimento',
        'action' => 'accion',
        'comment' => 'comentario',
        'other' => 'otro',
    ];

    private const CATEGORY_TO_TYPE = [
        'logro' => 'positive',
        'impedimento' => 'negative',
        'accion' => 'action',
        'comentario' => 'comment',
        'otro' => 'other',
    ];

    public function get_by_retrospective(int $sprintId): Collection
    {
        return FeedbackItem::where('sprint_id', $sprintId)
            ->orderBy('id')
            ->get()
            ->map(fn (FeedbackItem $item) => $this->format($item));
    }

    public function get_by_type(int $sprintId, string $type): Collection
    {
        $category = $this->category($type);
        return FeedbackItem::where('sprint_id', $sprintId)
            ->where('categoria', $category)
            ->orderBy('id')
            ->get()
            ->map(fn (FeedbackItem $item) => $this->format($item));
    }

    public function get_feedback(int $id): array
    {
        return $this->format($this->find($id));
    }

    public function create(array $data): array
    {
        $normalized = $this->normalize($data);
        if (empty($normalized['sprint_id'])
            || empty($normalized['categoria'])
            || trim((string) ($normalized['descripcion'] ?? '')) === ''
        ) {
            throw new InvalidArgumentException('Se requieren sprint_id, categoria y descripcion.');
        }

        $item = new FeedbackItem();
        $item->sprint_id = (int) $normalized['sprint_id'];
        $item->categoria = $this->category($normalized['categoria']);
        $item->descripcion = $normalized['descripcion'];
        $item->cumplida = $normalized['cumplida'] ?? null;
        $item->fecha_revision = $normalized['fecha_revision'] ?? null;
        $item->save();

        return $this->format($item);
    }

    public function create_bulk(array $data): array
    {
        $sprintId = $data['sprint_id'] ?? $data['retrospective_id'] ?? null;
        if (!$sprintId || empty($data['items']) || !is_array($data['items'])) {
            throw new InvalidArgumentException('Se requieren sprint_id y una lista items.');
        }

        $created = [];
        foreach ($data['items'] as $itemData) {
            $itemData['sprint_id'] = $sprintId;
            $created[] = $this->create($itemData);
        }

        return $created;
    }

    public function update(int $id, array $data): array
    {
        $item = $this->find($id);
        $normalized = $this->normalize($data);

        if (array_key_exists('categoria', $normalized)) {
            $item->categoria = $this->category($normalized['categoria']);
        }
        if (array_key_exists('descripcion', $normalized)) {
            if (trim((string) $normalized['descripcion']) === '') {
                throw new InvalidArgumentException('La descripción no puede estar vacía.');
            }
            $item->descripcion = $normalized['descripcion'];
        }
        if (array_key_exists('cumplida', $normalized)) {
            $item->cumplida = $normalized['cumplida'];
        }
        if (array_key_exists('fecha_revision', $normalized)) {
            $item->fecha_revision = $normalized['fecha_revision'];
        }
        if (array_key_exists('sprint_id', $normalized)) {
            $item->sprint_id = (int) $normalized['sprint_id'];
        }

        $item->save();
        return $this->format($item);
    }

    public function delete(int $id): array
    {
        $item = $this->find($id);
        $result = $this->format($item);
        $item->delete();
        return $result;
    }

    private function find(int $id): FeedbackItem
    {
        $item = FeedbackItem::find($id);
        if (!$item) {
            throw (new ModelNotFoundException())->setModel(FeedbackItem::class, [$id]);
        }
        return $item;
    }

    private function normalize(array $data): array
    {
        if (!array_key_exists('sprint_id', $data) && array_key_exists('retrospective_id', $data)) {
            $data['sprint_id'] = $data['retrospective_id'];
        }
        if (!array_key_exists('categoria', $data) && array_key_exists('type', $data)) {
            $data['categoria'] = $data['type'];
        }
        if (!array_key_exists('descripcion', $data)) {
            if (array_key_exists('description', $data) || array_key_exists('title', $data)) {
                $data['descripcion'] = $data['description'] ?? $data['title'];
            }
        }
        if (!array_key_exists('fecha_revision', $data) && array_key_exists('due_date', $data)) {
            $data['fecha_revision'] = $data['due_date'];
        }
        return $data;
    }

    private function category(string $value): string
    {
        $category = self::TYPE_TO_CATEGORY[$value] ?? $value;
        if (!array_key_exists($category, self::CATEGORY_TO_TYPE)) {
            throw new InvalidArgumentException('Categoría inválida.');
        }
        return $category;
    }

    private function format(FeedbackItem $item): array
    {
        $row = $item->toArray();
        $type = self::CATEGORY_TO_TYPE[$row['categoria']];

        return [
            'id' => $row['id'],
            'sprint_id' => $row['sprint_id'],
            'retrospective_id' => $row['sprint_id'],
            'categoria' => $row['categoria'],
            'type' => $type,
            'descripcion' => $row['descripcion'],
            'description' => $row['descripcion'],
            'title' => $row['descripcion'],
            'cumplida' => $row['cumplida'],
            'fecha_revision' => $row['fecha_revision'],
            'due_date' => $row['fecha_revision'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'priority' => 'medium',
            'assignee' => null,
            'order_index' => $row['id'],
        ];
    }
}
