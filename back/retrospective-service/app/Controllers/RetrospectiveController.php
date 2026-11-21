<?php

namespace App\Controllers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RetrospectiveController
{
    public function get_retrospectives(): array
    {
        return Capsule::table('retro_items')
            ->distinct()
            ->pluck('sprint_id')
            ->map(fn ($sprintId) => $this->virtualRetrospective((int) $sprintId))
            ->all();
    }

    public function get_retrospective(int $id): array
    {
        return $this->virtualRetrospective($id);
    }

    public function get_retrospective_by_sprint(int $sprintId): array
    {
        return $this->get_retrospective($sprintId);
    }

    public function create(array $data): array
    {
        $sprintId = (int) $data['sprint_id'];
        if (!Capsule::table('sprints')->where('id', $sprintId)->exists()) {
            throw (new ModelNotFoundException())->setModel('sprints', [$sprintId]);
        }

        return $this->virtualRetrospective($sprintId);
    }

    public function delete(int $id): array
    {
        $sprint = Capsule::table('sprints')->where('id', $id)->first();
        if (!$sprint) {
            throw (new ModelNotFoundException())->setModel('sprints', [$id]);
        }

        Capsule::table('retro_items')->where('sprint_id', $id)->delete();
        return $this->virtualRetrospective($id);
    }

    private function virtualRetrospective(int $sprintId): array
    {
        $sprint = Capsule::table('sprints')->where('id', $sprintId)->first();
        if (!$sprint) {
            throw (new ModelNotFoundException())->setModel('sprints', [$sprintId]);
        }

        $hasItems = Capsule::table('retro_items')->where('sprint_id', $sprintId)->exists();

        return [
            'id' => $sprintId,
            'sprint_id' => $sprintId,
            'date' => $sprint->fecha_fin,
            'status' => $hasItems ? 'in_progress' : 'pending',
            'participants' => [],
        ];
    }
}
