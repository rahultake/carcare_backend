<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementBar extends Model
{
    protected $fillable = [
        'text_before',
        'highlight_text',
        'text_after',
        'button_text',
        'button_url',
        'status'
    ];
}