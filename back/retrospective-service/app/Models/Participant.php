<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $table = 'retrospective_participants';
    public $timestamps = true;
    protected $fillable = ['retrospective_id', 'name', 'role'];

    public function retrospective()
    {
        return $this->belongsTo(Retrospective::class, 'retrospective_id');
    }
}