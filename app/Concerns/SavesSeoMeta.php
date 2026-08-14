<?php

namespace App\Concerns;

trait SavesSeoMeta
{
    protected function saveSeo(object $model, array $validated): void
    {
        if (empty($validated['seo'])) {
            return;
        }

        $model->seo()->updateOrCreate(
            ['seoable_id' => $model->id, 'seoable_type' => $model::class],
            $validated['seo']
        );
    }
}