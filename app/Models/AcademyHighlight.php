<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademyHighlight extends Model
{
    protected $table = 'academy_highlights';

    protected $fillable = [
        'course_id',
        'title',
        'description'
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
            HighlightItem::class,
            'highlight_id'
        );
    }
}