<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sprint extends Model
{
    protected $table = 'sprints';
    public $timestamps = true;
    protected $fillable = ['team_id', 'name', 'number', 'goal', 'start_date', 'end_date', 'status'];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}

class Team extends Model
{
    protected $table = 'teams';
    public $timestamps = true;
    protected $fillable = ['name', 'description', 'scrum_master', 'product_owner'];

    public function sprints()
    {
        return $this->hasMany(Sprint::class, 'team_id');
    }
}