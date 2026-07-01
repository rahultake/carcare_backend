<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecializationProgram extends Model
{
    protected $table = 'specialization_programs';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'image'
    ];

    public function course()
    {
        return $this->belongsTo(
            TrainingCourse::class,
            'course_id'
        );
    }

    public function items()
    {
        return $this->hasMany(
            SpecializationItem::class,
            'specialization_id'
        );
    }
}