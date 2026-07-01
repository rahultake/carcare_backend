<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BenefitItem extends Model
{
    protected $table = 'training_benefit_items';

    protected $fillable = [
        'benefit_id',
        'item'
    ];

    public function benefit()
    {
        return $this->belongsTo(
            TrainingBenefit::class,
            'benefit_id'
        );
    }
}