<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Article extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'image', 'user_id', 'is_published'];

    public function user()
    {
        return $this->belongsTo(User::class);
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