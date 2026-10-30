<?php

namespace App\Blocks\Rules\Concerns;

trait HasDesignRules
{
    /** Options d'apparence dans data.design (data.style est deja pris par bouton et separateur). */
    protected function designRules(): array
    {
        return [
            'design' => 'nullable|array',
            'design.align' => 'nullable|in:left,center,right',
        ];
    }
}
