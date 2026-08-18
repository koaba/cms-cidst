<?php

namespace App\Concerns;

trait SavesSeoMeta
{
    protected function saveSeo(object $model, array $validated): void
    {
        if (empty($validated['seo'])) {
            return;
        }

        $model->seo()->updateOrCreate([], $validated['seo']);
    }
}