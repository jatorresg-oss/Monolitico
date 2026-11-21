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
}