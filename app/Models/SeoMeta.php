<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoMeta extends Model
{
    protected $fillable = [
        'meta_title',
        'meta_description',
        'og_image',
        'canonical_url',
        'no_index',
    ];

    protected function casts(): array
    {
        return ['no_index' => 'boolean'];
    }

    public function seoable()
    {
        return $this->morphTo();
    }
}