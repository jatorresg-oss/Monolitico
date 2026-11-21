<?php

namespace App\Controllers;

use App\Models\Sprint;
use Illuminate\Database\Eloquent\Collection;

class SprintController
{
    public function get_sprints(): Collection
    {
        return Sprint::orderByDesc('fecha_inicio')->orderByDesc('id')->get();
    }

    public function get_sprint(int $id): Sprint
    {
        return Sprint::findOrFail($id);
    }

    public function create(array $data): Sprint
    {
        $sprint = new Sprint();
        $sprint->nombre = trim($data['nombre']);
        $sprint->fecha_inicio = $data['fecha_inicio'];
        $sprint->fecha_fin = $data['fecha_fin'];
        $sprint->save();

        return $sprint->refresh();
    }

    public function update(int $id, array $data): Sprint
    {
        $sprint = Sprint::findOrFail($id);

        if (isset($data['nombre'])) {
            $sprint->nombre = trim($data['nombre']);
        }
        if (isset($data['fecha_inicio'])) {
            $sprint->fecha_inicio = $data['fecha_inicio'];
        }
        if (isset($data['fecha_fin'])) {
            $sprint->fecha_fin = $data['fecha_fin'];
        }

        $sprint->save();

        return $sprint->refresh();
    }

    public function delete(int $id): Sprint
    {
        $sprint = Sprint::findOrFail($id);
        $sprint->delete();

        return $sprint;
    }
}
