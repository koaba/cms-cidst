<?php

namespace App\Models;

use App\Concerns\HasPublicVisibility;
use App\Concerns\HasSeo;
use App\Contracts\HasPublicUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model implements HasPublicUrl
{
    use HasFactory, HasSeo, HasPublicVisibility;

    protected $fillable = ['title', 'slug', 'content', 'user_id', 'is_published'];
    protected $casts = ['is_published' => 'boolean'];

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

    public function publicUrl(): string
    {
        return route('pages.show', $this);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($page) {
            $baseSlug = Str::slug($page->title);
            $slug = $baseSlug;
            $counter = 1;
            while (static::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }
            $page->slug = $slug;
        });
    }
}