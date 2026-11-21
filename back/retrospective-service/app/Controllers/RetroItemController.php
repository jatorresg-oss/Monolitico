<?php

namespace App\Controllers;

use App\Models\RetroItem;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\Collection;

class RetroItemController
{
    public const CATEGORIAS = ['accion', 'logro', 'impedimento', 'comentario', 'otro'];

    public function get_items(?int $sprintId = null, ?string $categoria = null): Collection
    {
        $query = RetroItem::with('sprint')->orderBy('created_at');

        if ($sprintId !== null) {
            $query->where('sprint_id', $sprintId);
        }
        if ($categoria !== null) {
            $query->where('categoria', $categoria);
        }

        return $query->get();
    }

    public function get_items_by_sprint(int $sprintId): Collection
    {
        return RetroItem::with('sprint')
            ->where('sprint_id', $sprintId)
            ->orderBy('created_at')
            ->get();
    }

    public function get_item(int $id): RetroItem
    {
        return RetroItem::with('sprint')->findOrFail($id);
    }

    public function create(array $data): RetroItem
    {
        Sprint::findOrFail((int) $data['sprint_id']);

        $item = new RetroItem();
        $item->sprint_id = (int) $data['sprint_id'];
        $item->categoria = $data['categoria'];
        $item->descripcion = trim($data['descripcion']);
        $this->applyActionFields($item, $data);
        $item->save();

        return $item->refresh()->load('sprint');
    }

    public function update(int $id, array $data): RetroItem
    {
        $item = RetroItem::findOrFail($id);

        if (isset($data['sprint_id'])) {
            Sprint::findOrFail((int) $data['sprint_id']);
            $item->sprint_id = (int) $data['sprint_id'];
        }
        if (isset($data['categoria'])) {
            $item->categoria = $data['categoria'];
        }
        if (isset($data['descripcion'])) {
            $item->descripcion = trim($data['descripcion']);
        }

        $this->applyActionFields($item, $data);
        $item->save();

        return $item->refresh()->load('sprint');
    }

    public function delete(int $id): RetroItem
    {
        $item = RetroItem::with('sprint')->findOrFail($id);
        $item->delete();

        return $item;
    }

    private function applyActionFields(RetroItem $item, array $data): void
    {
        if ($item->categoria !== 'accion') {
            $item->cumplida = null;
            $item->fecha_revision = null;
            return;
        }

        if (array_key_exists('cumplida', $data)) {
            $item->cumplida = $data['cumplida'] === null ? null : (bool) $data['cumplida'];
        }
        if (array_key_exists('fecha_revision', $data)) {
            $item->fecha_revision = empty($data['fecha_revision']) ? null : $data['fecha_revision'];
        }
    }
}
