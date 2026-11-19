<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ancien slug d'une page renommee : sert a rediriger en 301 vers l'adresse actuelle (SEO).
 */
class PageSlugRedirect extends Model
{
    protected $fillable = ['page_id', 'old_slug'];

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
