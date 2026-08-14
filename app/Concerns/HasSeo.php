<?php

namespace App\Concerns;

trait HasSeo
{
    public function seo()
    {
        return $this->morphOne(\App\Models\SeoMeta::class, 'seoable');
    }

    public function seoTitle(): string
    {
        return $this->seo?->meta_title ?: $this->title ?: config('app.name');
    }

    public function seoDescription(): string
    {
        return $this->seo?->meta_description ?: str($this->excerpt ?? '')->limit(160)->toString();
    }
}