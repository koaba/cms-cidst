<?php

namespace App\Concerns;

use App\Models\SeoMeta;

trait HasSeo
{
    public function seo()
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}
