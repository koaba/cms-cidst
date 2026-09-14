<?php

namespace App\Models;

use App\Traits\HasOrderedMediaCollection;
use App\Traits\HasOrphanMediaCleanup;
use Illuminate\Database\Eloquent\Model;

class PageBlock extends Model
{
    use HasOrderedMediaCollection, HasOrphanMediaCleanup;

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
            ->withPivot('order', 'alt', 'caption')
            ->orderByPivot('order');
    }
}