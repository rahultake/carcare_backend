<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleItem extends Model
{
    protected $table = 'training_module_items';

    protected $fillable = [
        'module_id',
        'item'
    ];

    public function module()
    {
        return $this->belongsTo(
            CourseModule::class,
            'module_id'
        );
    }
}