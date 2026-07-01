<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingCourse extends Model
{
    protected $table = 'training_courses';

    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'duration',
        'certification',
        'batch_size',
        'rating',
        'students_trained',
        'job_placement',
        'experience_years',
        'icon_image',
        'video_url',
        'benefits_title',
        'benefits_subtitle',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'status'
    ];

    public function modules()
    {
        return $this->hasMany(CourseModule::class,'course_id');
    }

    public function benefits()
    {
        return $this->hasMany(
            TrainingBenefit::class,
            'course_id'
        );
    }

    public function highlights()
    {
        return $this->hasMany(
            AcademyHighlight::class,
            'course_id'
        );
    }

    public function specializationPrograms()
    {
        return $this->hasMany(
            SpecializationProgram::class,
            'course_id'
        );
    }
}