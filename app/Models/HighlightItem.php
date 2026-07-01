<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HighlightItem extends Model
{
    protected $table = 'academy_highlight_items';

    protected $fillable = [
        'highlight_id',
        'item'
    ];

    public function highlight()
    {
        return $this->belongsTo(
            AcademyHighlight::class,
            'highlight_id'
        );
    }
}