<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingInquiry extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'phone_number',
        'city',
        'state',
        'course_interest',
        'message',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];
}