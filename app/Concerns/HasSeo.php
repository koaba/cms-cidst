<?php

namespace App\Concerns;

trait HasSeo
{
    public function seo()
    {
        return $this->morphOne(\App\Models\SeoMeta::class, 'seoable');
    }
}