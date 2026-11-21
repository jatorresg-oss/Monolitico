<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackItem extends Model
{
    protected $table = 'feedback_items';
    public $timestamps = true;
    protected $fillable = ['retrospective_id', 'type', 'title', 'description', 'priority', 'assignee', 'due_date', 'order_index'];
    protected $casts = [
        'due_date' => 'date',
    ];

    public function retrospective()
    {
        return $this->belongsTo(Retrospective::class, 'retrospective_id');
    }
}