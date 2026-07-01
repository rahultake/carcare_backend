<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseModule extends Model
{
    protected $table = 'training_course_modules';

    protected $fillable = [
        'course_id',
        'day_name',
        'title'
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
            ModuleItem::class,
            'module_id'
        );
    }
}