<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ArticleImage extends Model
{
    use HasFactory;

    protected $fillable = ['article_id', 'path', 'order'];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (ArticleImage $image) {
            Storage::disk('public')->delete($image->path);
        });
    }
}