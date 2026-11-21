<?php

namespace App\Controllers;

use App\Models\RetroItem;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\Collection;

class ActionTrackingController
{
    public function get_actions_by_sprint(int $sprintId): Collection
    {
        return RetroItem::with('sprint')
            ->where('sprint_id', $sprintId)
            ->where('categoria', 'accion')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Acciones de sprints anteriores que siguen pendientes y deben revisarse
     * en el sprint indicado (cargar acuerdos de la retrospectiva anterior).
     */
    public function get_pending_actions(int $sprintId): Collection
    {
        Sprint::findOrFail($sprintId);

        return RetroItem::with('sprint')
            ->where('categoria', 'accion')
            ->where('sprint_id', '<', $sprintId)
            ->where(function ($query) {
                $query->whereNull('cumplida')->orWhere('cumplida', 0);
            })
            ->orderByDesc('sprint_id')
            ->orderBy('created_at')
            ->get();
    }

    public function get_action(int $id): RetroItem
    {
        return RetroItem::with('sprint')->findOrFail($id);
    }

    public function get_history(int $id): RetroItem
    {
        return RetroItem::with('sprint')->findOrFail($id);
    }

    public function update(int $id, array $data): RetroItem
    {
        $item = RetroItem::findOrFail($id);
        if ($item->categoria !== 'accion') {
            throw new \InvalidArgumentException('El item no es una acción.');
        }

        if (array_key_exists('descripcion', $data)) {
            $item->descripcion = trim((string) $data['descripcion']);
        }
        if (array_key_exists('cumplida', $data)) {
            $item->cumplida = $data['cumplida'] === null ? null : (bool) $data['cumplida'];
        }
        if (array_key_exists('fecha_revision', $data)) {
            $item->fecha_revision = empty($data['fecha_revision']) ? null : $data['fecha_revision'];
        }

        $item->save();

        return $item->refresh()->load('sprint');
    }

    public function get_stats(?int $sprintId = null): array
    {
        $query = RetroItem::where('categoria', 'accion');
        if ($sprintId !== null) {
            Sprint::findOrFail($sprintId);
            $query->where('sprint_id', $sprintId);
        }

        $total = (clone $query)->count();
        $cumplidas = (clone $query)->where('cumplida', 1)->count();
        $pendientes = (clone $query)
            ->where(function ($q) {
                $q->whereNull('cumplida')->orWhere('cumplida', 0);
            })
            ->count();

        return [
            'sprint_id' => $sprintId,
            'total' => $total,
            'cumplidas' => $cumplidas,
            'pendientes' => $pendientes,
            'tasa_cumplimiento' => $total > 0 ? round(($cumplidas / $total) * 100, 1) : 0,
        ];
    }
}
