<?php

namespace App\Controllers;

use App\Models\FeedbackItem;
use App\Models\Retrospective;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class FeedbackController
{
    function get_by_retrospective(int $retroId): Collection
    {
        return FeedbackItem::where('retrospective_id', $retroId)
            ->orderBy('type')
            ->orderBy('order_index')
            ->get();
    }

    function get_by_type(int $retroId, string $type): Collection
    {
        return FeedbackItem::where('retrospective_id', $retroId)
            ->where('type', $type)
            ->orderBy('order_index')
            ->get();
    }

    function get_feedback(int $id): FeedbackItem
    {
        return FeedbackItem::findOrFail($id);
    }

    function create(array $data): FeedbackItem
    {
        Retrospective::findOrFail($data['retrospective_id']);
        
        $feedback = new FeedbackItem();
        $feedback->retrospective_id = $data['retrospective_id'];
        $feedback->type = $data['type'];
        $feedback->title = $data['title'];
        $feedback->description = $data['description'] ?? null;
        $feedback->priority = $data['priority'] ?? 'medium';
        $feedback->assignee = $data['assignee'] ?? null;
        $feedback->due_date = $data['due_date'] ?? null;
        $feedback->order_index = $data['order_index'] ?? 0;
        $feedback->save();
        return $feedback;
    }

    function create_bulk(array $data): array
    {
        Retrospective::findOrFail($data['retrospective_id']);
        
        $items = [];
        foreach ($data['items'] as $itemData) {
            $feedback = new FeedbackItem();
            $feedback->retrospective_id = $data['retrospective_id'];
            $feedback->type = $itemData['type'];
            $feedback->title = $itemData['title'];
            $feedback->description = $itemData['description'] ?? null;
            $feedback->priority = $itemData['priority'] ?? 'medium';
            $feedback->assignee = $itemData['assignee'] ?? null;
            $feedback->due_date = $itemData['due_date'] ?? null;
            $feedback->order_index = $itemData['order_index'] ?? 0;
            $feedback->save();
            $items[] = $feedback;
        }
        return $items;
    }

    function update(int $id, array $data): FeedbackItem
    {
        $feedback = FeedbackItem::findOrFail($id);
        
        if (isset($data['title'])) $feedback->title = $data['title'];
        if (isset($data['description'])) $feedback->description = $data['description'];
        if (isset($data['priority'])) $feedback->priority = $data['priority'];
        if (isset($data['assignee'])) $feedback->assignee = $data['assignee'];
        if (isset($data['due_date'])) $feedback->due_date = $data['due_date'];
        if (isset($data['order_index'])) $feedback->order_index = $data['order_index'];
        
        $feedback->save();
        return $feedback;
    }

    function delete(int $id): FeedbackItem
    {
        $feedback = FeedbackItem::findOrFail($id);
        $feedback->delete();
        return $feedback;
    }
}