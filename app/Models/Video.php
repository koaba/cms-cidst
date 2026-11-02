<?php

namespace App\Models;

use App\Support\VideoEmbed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    protected $fillable = [
        'videoable_type', 'videoable_id',
        'source_type', 'path', 'url', 'title', 'order', 'apply_watermark',
    ];

    protected $casts = [
        'apply_watermark' => 'boolean',
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
        if ($this->source_type !== 'external') {
            return null;
        }

        return VideoEmbed::embedUrl($this->url);
    }

    public function getYoutubeThumbnailAttribute(): ?string
    {
        $id = VideoEmbed::youtubeId($this->url);

        return $id ? "https://img.youtube.com/vi/{$id}/hqdefault.jpg" : null;
    }
}
