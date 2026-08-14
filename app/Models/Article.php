<?php

namespace App\Models;

use App\Concerns\HasPublicVisibility;
use App\Concerns\HasSeo;
use App\Contracts\HasPublicUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Article extends Model implements HasPublicUrl
{
    use HasSeo;
    use HasPublicVisibility;
    use HasFactory;

    protected $fillable = ['title', 'slug', 'content', 'image', 'user_id', 'is_published', 'published_at', 'gallery_display'];
    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function media()
    {
        return $this->morphToMany(Media::class, 'mediable')
            ->withPivot('order')
            ->orderByPivot('order');
    }

    public function diaporamas()
    {
        return $this->morphMany(Diaporama::class, 'diaporamable')->orderBy('order');
    }

    public function videos()
    {
        return $this->morphMany(Video::class, 'videoable')->orderBy('order');
    }

    public function publicUrl(): string
    {
        return route('blog.show', $this);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($article) {
            $baseSlug = Str::slug($article->title);
            $slug = $baseSlug;
            $counter = 1;
            while (static::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $article->slug = $slug;
        });
    }
}