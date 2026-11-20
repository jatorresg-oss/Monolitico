<?php

namespace App\Controllers;

use App\Models\ActionTracking;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class ActionTrackingController
{
    function get_pending_actions(int $sprintId): Collection
    {
        // Obtener acciones de retrospectivas anteriores que no se han revisado en este sprint
        $subquery = ActionTracking::select('original_feedback_id')
            ->where('review_sprint_id', $sprintId);
        
        // This would need cross-service calls in real implementation
        // For now, return empty - frontend will call feedback-service directly
        return ActionTracking::where('review_sprint_id', $sprintId)->get();
    }

    function get_tracking_by_sprint(int $sprintId): Collection
    {
        return ActionTracking::where('review_sprint_id', $sprintId)->get();
    }

    function get_tracking_history(int $feedbackId): Collection
    {
        return ActionTracking::where('original_feedback_id', $feedbackId)
            ->orderBy('reviewed_at', 'desc')
            ->get();
    }

    function create(array $data): ActionTracking
    {
        $tracking = new ActionTracking();
        $tracking->original_feedback_id = $data['original_feedback_id'];
        $tracking->review_sprint_id = $data['review_sprint_id'];
        $tracking->status = $data['status'];
        $tracking->completion_percentage = $data['completion_percentage'] ?? 0;
        $tracking->comments = $data['comments'] ?? null;
        $tracking->reviewed_by = $data['reviewed_by'] ?? null;
        $tracking->save();
        
        return $tracking;
    }

    function update(int $id, array $data): ActionTracking
    {
        $tracking = ActionTracking::findOrFail($id);
        
        if (isset($data['status'])) $tracking->status = $data['status'];
        if (isset($data['completion_percentage'])) $tracking->completion_percentage = $data['completion_percentage'];
        if (isset($data['comments'])) $tracking->comments = $data['comments'];
        if (isset($data['reviewed_by'])) $tracking->reviewed_by = $data['reviewed_by'];
        
        $tracking->save();
        return $tracking;
    }

    function delete(int $id): ActionTracking
    {
        $tracking = ActionTracking::findOrFail($id);
        $tracking->delete();
        return $tracking;
    }
}