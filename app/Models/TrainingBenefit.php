<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingBenefit extends Model
{
    protected $table = 'training_benefits';

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
            BenefitItem::class,
            'benefit_id'
        );
    }
}