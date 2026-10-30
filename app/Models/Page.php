<?php

namespace App\Models;

use App\Concerns\HasPublicVisibility;
use App\Concerns\HasSeo;
use App\Contracts\HasPublicUrl;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model implements HasPublicUrl
{
    use HasFactory, HasPublicVisibility, HasSeo;

    protected $fillable = ['title', 'slug', 'content', 'user_id', 'is_published', 'published_at'];

    protected $casts = ['is_published' => 'boolean', 'published_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->morphToMany(Media::class, 'mediable')
            ->withPivot('order')
            ->orderByPivot('order');
    }

    public function blocks()
    {
        return $this->hasMany(PageBlock::class)->orderBy('order');
    }

    /**
     * Charge seo, medias et tous les blocs (avec leurs medias) en 4 requetes,
     * puis rattache en memoire la relation `children` de chaque bloc, a toute
     * profondeur. Evite le N+1 du rendu public (les blocs portent tous page_id).
     */
    public function loadBlockTree(): static
    {
        $this->loadMissing(['seo', 'media', 'blocks.media']);

        $byParent = $this->blocks->groupBy(fn (PageBlock $block) => $block->parent_id ?? 0);

        foreach ($this->blocks as $block) {
            $block->setRelation('children', $byParent->get($block->id, new Collection));
        }

        return $this;
    }

    /** draft, scheduled ou published, calcule depuis is_published et published_at. */
    public function getStatusAttribute(): string
    {
        if (! $this->is_published) {
            return 'draft';
        }

        return $this->isPubliclyVisible() ? 'published' : 'scheduled';
    }

    public function publicUrl(): string
    {
        return route('pages.show', $this);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($page) {
            $baseSlug = Str::slug($page->slug ?: $page->title);
            $slug = $baseSlug;
            $counter = 1;
            while (static::where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$counter++;
            }
            $page->slug = $slug;
        });
    }
}
