<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Retrospective extends Model
{
    protected $table = 'retrospectives';
    public $timestamps = true;
    protected $fillable = ['sprint_id', 'facilitator', 'date', 'duration_minutes', 'status', 'notes'];
    protected $casts = [
        'date' => 'date',
    ];

    public function sprint()
    {
        return $this->belongsTo(Sprint::class, 'sprint_id');
    }

    public function participants()
    {
        return $this->hasMany(Participant::class, 'retrospective_id');
    }

    public function feedbackItems()
    {
        return $this->hasMany(FeedbackItem::class, 'retrospective_id');
    }
}