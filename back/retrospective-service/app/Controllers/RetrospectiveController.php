<?php

namespace App\Controllers;

use App\Models\Retrospective;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class RetrospectiveController
{
    function get_retrospectives(): Collection
    {
        return Retrospective::with(['sprint.team', 'participants'])->orderBy('date', 'desc')->get();
    }

    function get_retrospective(int $id): Retrospective
    {
        return Retrospective::with(['sprint.team', 'participants'])->findOrFail($id);
    }

    function get_retrospective_by_sprint(int $sprintId): Retrospective
    {
        return Retrospective::with(['sprint.team', 'participants'])
            ->where('sprint_id', $sprintId)
            ->firstOrFail();
    }

    function create(array $data): Retrospective
    {
        $retro = new Retrospective();
        $retro->sprint_id = $data['sprint_id'];
        $retro->facilitator = $data['facilitator'] ?? null;
        $retro->date = $data['date'];
        $retro->duration_minutes = $data['duration_minutes'] ?? 60;
        $retro->status = $data['status'] ?? 'pending';
        $retro->notes = $data['notes'] ?? null;
        $retro->save();

        if (isset($data['participants']) && is_array($data['participants'])) {
            foreach ($data['participants'] as $participant) {
                $p = new Participant();
                $p->retrospective_id = $retro->id;
                $p->name = $participant['name'];
                $p->role = $participant['role'] ?? 'developer';
                $p->save();
            }
        }

        return $retro->load(['sprint.team', 'participants']);
    }

    function update(int $id, array $data): Retrospective
    {
        $retro = Retrospective::findOrFail($id);
        
        if (isset($data['facilitator'])) $retro->facilitator = $data['facilitator'];
        if (isset($data['date'])) $retro->date = $data['date'];
        if (isset($data['duration_minutes'])) $retro->duration_minutes = $data['duration_minutes'];
        if (isset($data['status'])) $retro->status = $data['status'];
        if (isset($data['notes'])) $retro->notes = $data['notes'];
        
        $retro->save();

        if (isset($data['participants']) && is_array($data['participants'])) {
            Participant::where('retrospective_id', $id)->delete();
            
            foreach ($data['participants'] as $participant) {
                $p = new Participant();
                $p->retrospective_id = $retro->id;
                $p->name = $participant['name'];
                $p->role = $participant['role'] ?? 'developer';
                $p->save();
            }
        }

        return $retro->load(['sprint.team', 'participants']);
    }

    function complete(int $id): Retrospective
    {
        $retro = Retrospective::findOrFail($id);
        $retro->status = 'completed';
        $retro->save();
        return $retro->load(['sprint.team', 'participants']);
    }

    function delete(int $id): Retrospective
    {
        $retro = Retrospective::findOrFail($id);
        $retro->delete();
        return $retro;
    }
}