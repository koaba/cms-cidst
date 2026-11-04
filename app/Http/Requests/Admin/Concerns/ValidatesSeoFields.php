<?php

namespace App\Http\Requests\Admin\Concerns;

/**
 * Regles de validation du bloc "seo" (table seo_metas), partagees entre les
 * formulaires de pages et d'articles. Un futur modele HasSeo les reprend tel quel.
 */
trait ValidatesSeoFields
{
    protected function seoRules(): array
    {
        return [
            'seo' => 'nullable|array',
            'seo.meta_title' => 'nullable|string|max:60',
            'seo.meta_description' => 'nullable|string|max:320',
            'seo.canonical_url' => 'nullable|url:http,https|max:255',
            'seo.no_index' => 'nullable|boolean',
        ];
    }
}
