<?php

namespace App\Controllers;

use App\Models\Sprint;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class SprintController
{
    function get_sprints(): Collection
    {
        return Sprint::with('team')->orderBy('number', 'desc')->get();
    }

    function get_sprint(int $id): Sprint
    {
        return Sprint::with('team')->findOrFail($id);
    }

    function get_sprints_by_team(int $teamId): Collection
    {
        return Sprint::where('team_id', $teamId)->orderBy('number', 'desc')->get();
    }

    function create(array $data): Sprint
    {
        Team::findOrFail($data['team_id']);

        $number = $data['number'] ?? null;
        if (!$number) {
            $number = Sprint::where('team_id', $data['team_id'])->max('number') + 1;
        }
        
        $sprint = new Sprint();
        $sprint->team_id = $data['team_id'];
        $sprint->name = $data['name'];
        $sprint->number = $number;
        $sprint->goal = $data['goal'] ?? null;
        $sprint->start_date = $data['start_date'];
        $sprint->end_date = $data['end_date'];
        $sprint->status = $data['status'] ?? 'planned';
        $sprint->save();
        return $sprint->load('team');
    }

    function update(int $id, array $data): Sprint
    {
        $sprint = Sprint::findOrFail($id);
        
        if (isset($data['team_id'])) {
            Team::findOrFail($data['team_id']);
            $sprint->team_id = $data['team_id'];
        }
        if (isset($data['name'])) $sprint->name = $data['name'];
        if (isset($data['number'])) $sprint->number = $data['number'];
        if (isset($data['goal'])) $sprint->goal = $data['goal'];
        if (isset($data['start_date'])) $sprint->start_date = $data['start_date'];
        if (isset($data['end_date'])) $sprint->end_date = $data['end_date'];
        if (isset($data['status'])) $sprint->status = $data['status'];
        
        $sprint->save();
        return $sprint->load('team');
    }

    function delete(int $id): Sprint
    {
        $sprint = Sprint::findOrFail($id);
        $sprint->delete();
        return $sprint;
    }
}