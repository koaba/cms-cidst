<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\Page;
use Illuminate\Validation\Rule;

/**
 * Regles de validation partagees entre StorePageRequest et UpdatePageRequest,
 * extraites de Admin\PageController (store et update les dupliquaient).
 * Seule la regle d'unicite du slug differe : en edition, la page courante
 * est ignoree.
 */
trait ValidatesPageFields
{
    use ValidatesSeoFields;

    /**
     * @param  ?Page  $page  null en creation, la page existante en edition.
     */
    protected function pageRules(?Page $page): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'published_at' => 'required|date',
            'slug' => [
                'nullable', 'string', 'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('pages', 'slug')->ignore($page?->id),
            ],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_published' => 'nullable|boolean',
            ...$this->seoRules(),
        ];
    }
}
