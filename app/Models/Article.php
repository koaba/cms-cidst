<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Article extends Model
{
   protected $fillable = ['title', 'slug', 'content', 'image', 'user_id', 'is_published', 'published_at', 'gallery_display'];
    protected $casts = [
        'published_at' => 'datetime',
    ];

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