<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecializationItem extends Model
{
    protected $table = 'specialization_items';

    protected $fillable = [
        'specialization_id',
        'item'
    ];

    public function specialization()
    {
        return $this->belongsTo(
            SpecializationProgram::class,
            'specialization_id'
        );
    }
}