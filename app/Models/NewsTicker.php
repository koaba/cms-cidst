<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsTicker extends Model
{
    use HasFactory;
    protected $fillable = ['content', 'link_url', 'order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}