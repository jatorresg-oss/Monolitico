<?php

namespace App\Controllers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ActionTrackingController
{
    public function get_pending_actions(int $sprintId): Collection
    {
        $sprint = Capsule::table('sprints')->where('id', $sprintId)->first();
        if (!$sprint) {
            throw (new ModelNotFoundException())->setModel('sprints', [$sprintId]);
        }

        return Capsule::table('retro_items')
            ->join('sprints as source_sprints', 'retro_items.sprint_id', '=', 'source_sprints.id')
            ->where('retro_items.categoria', 'accion')
            ->where('source_sprints.fecha_fin', '<', $sprint->fecha_inicio)
            ->where(function ($query) {
                $query->whereNull('retro_items.cumplida')->orWhere('retro_items.cumplida', false);
            })
            ->where(function ($query) use ($sprint) {
                $query->whereNull('retro_items.fecha_revision')
                    ->orWhere('retro_items.fecha_revision', '<=', $sprint->fecha_inicio);
            })
            ->select('retro_items.*', 'source_sprints.nombre as sprint_nombre')
            ->orderBy('retro_items.id')
            ->get()
            ->map(fn ($item) => $this->format($item));
    }

    public function get_tracking_by_sprint(int $sprintId): Collection
    {
        return Capsule::table('retro_items')
            ->where('sprint_id', $sprintId)
            ->where('categoria', 'accion')
            ->orderBy('id')
            ->get()
            ->map(fn ($item) => $this->format($item));
    }

    public function get_tracking_history(int $feedbackId): Collection
    {
        $item = Capsule::table('retro_items')
            ->where('id', $feedbackId)
            ->where('categoria', 'accion')
            ->first();

        return collect($item ? [$this->format($item)] : []);
    }

    public function create(array $data): array
    {
        return $this->setCompletion(
            (int) $data['original_feedback_id'],
            $data['status'] === 'completed'
        );
    }

    public function update(int $id, array $data): array
    {
        if (!isset($data['status']) || !in_array($data['status'], ['completed', 'partial', 'not_completed', 'carried_over'], true)) {
            throw new InvalidArgumentException('Status inválido.');
        }

        return $this->setCompletion($id, $data['status'] === 'completed');
    }

    public function delete(int $id): void
    {
        throw new InvalidArgumentException('El esquema retro_items no almacena un historial de seguimiento independiente.');
    }

    public function get(int $id): array
    {
        $item = Capsule::table('retro_items')
            ->where('id', $id)
            ->where('categoria', 'accion')
            ->first();
        if (!$item) {
            throw (new ModelNotFoundException())->setModel('retro_items', [$id]);
        }
        return $this->format($item);
    }

    private function setCompletion(int $itemId, bool $completed): array
    {
        $item = Capsule::table('retro_items')
            ->where('id', $itemId)
            ->where('categoria', 'accion')
            ->first();
        if (!$item) {
            throw (new ModelNotFoundException())->setModel('retro_items', [$itemId]);
        }

        Capsule::table('retro_items')->where('id', $itemId)->update(['cumplida' => $completed]);

        return $this->get($itemId);
    }

    private function format($item): array
    {
        $completed = $item->cumplida !== null && (bool) $item->cumplida;
        return [
            'id' => (int) $item->id,
            'original_feedback_id' => (int) $item->id,
            'sprint_id' => (int) $item->sprint_id,
            'review_sprint_id' => (int) $item->sprint_id,
            'status' => $completed ? 'completed' : 'not_completed',
            'completion_percentage' => $completed ? 100 : 0,
            'description' => $item->descripcion,
            'title' => $item->descripcion,
            'created_at' => $item->created_at,
            'fecha_revision' => $item->fecha_revision,
            'sprint_nombre' => $item->sprint_nombre ?? null,
        ];
    }
}
