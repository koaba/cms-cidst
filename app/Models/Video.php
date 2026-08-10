<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    protected $fillable = [
        'videoable_type', 'videoable_id',
        'source_type', 'path', 'url', 'title', 'order',
    ];

    public function videoable()
    {
        return $this->morphTo();
    }
public function getMimeAttribute(): ?string
{
    return $this->source_type === 'upload' && $this->path
        ? Storage::disk('public')->mimeType($this->path)
        : null;
}
    public function getDisplayUrlAttribute(): string
    {
        return $this->source_type === 'upload'
            ? Storage::disk('public')->url($this->path)
            : $this->url;
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (Video $video) {
            if ($video->source_type === 'upload' && $video->path) {
                Storage::disk('public')->delete($video->path);
            }
        });

        }

        public function getEmbedUrlAttribute(): ?string
    {
        if ($this->source_type !== 'external' || !$this->url) {
            return null;
        }

        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $this->url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        if (preg_match('/vimeo\.com\/(\d+)/', $this->url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        return $this->url;
    }

    public function getYoutubeThumbnailAttribute(): ?string
    {
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $this->url ?? '', $m)) {
            return "https://img.youtube.com/vi/{$m[1]}/hqdefault.jpg";
        }
        return null;
    }
    
}
