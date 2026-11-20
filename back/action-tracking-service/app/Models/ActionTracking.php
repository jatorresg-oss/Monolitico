<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionTracking extends Model
{
    protected $table = 'action_tracking';
    public $timestamps = true;
    protected $fillable = ['original_feedback_id', 'review_sprint_id', 'status', 'completion_percentage', 'comments', 'reviewed_by'];
}