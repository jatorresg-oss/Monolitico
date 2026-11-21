<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetroItem extends Model
{
    protected $table = 'retro_items';
    public $timestamps = true;

    protected $fillable = [
        'sprint_id',
        'categoria',
        'descripcion',
        'cumplida',
        'fecha_revision',
    ];

    protected $casts = [
        'cumplida' => 'boolean',
        'fecha_revision' => 'date:Y-m-d',
    ];

    public function sprint()
    {
        return $this->belongsTo(Sprint::class, 'sprint_id');
    }
}
