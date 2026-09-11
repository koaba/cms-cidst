<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageBlock extends Model
{
    protected $fillable = ['page_id', 'type', 'data', 'order'];

    protected $casts = [
        'data' => 'array',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function media()
    {
        return $this->morphToMany(Media::class, 'mediable')
            ->withPivot('order')
            ->orderByPivot('order');
    }
}