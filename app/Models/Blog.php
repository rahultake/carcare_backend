<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;
    
    // Fillable fields
    protected $fillable = [
        'title',
        'slug',
        'category_id',
        'image',
        'short_description',
        'long_description',
        'created_at',
        'updated_at',
    ];

    // Optional: If you're not using timestamps
    // public $timestamps = false;

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getImageAttribute($value)
    {
        if ($value) {
            return asset($value);
            // OR if you specifically want /public/ in URL:
            // return url('public/' . $value);
        }
        return null;
    }
}
