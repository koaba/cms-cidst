<?php

namespace App\Models;

use App\Jobs\GenerateMediaThumbnail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = ['path', 'thumbnail_path', 'original_name', 'mime_type', 'size', 'type', 'source_type', 'url', 'apply_watermark'];

    protected $casts = [
        'apply_watermark' => 'boolean',
    ];

    public function mediables()
    {
        return $this->hasMany(Mediable::class);
    }

    public function usages(): array
    {
        return $this->mediables
            ->map(function ($pivot) {
                $model = class_exists($pivot->mediable_type)
                    ? $pivot->mediable_type::find($pivot->mediable_id)
                    : null;

                return $model ? [
                    'type' => class_basename($pivot->mediable_type),
                    'title' => $model->title ?? $model->name ?? $model->label ?? '(sans titre)',
                ] : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    public function getThumbnailUrlAttribute(): string
    {
        if ($this->type === 'video' && $this->source_type === 'external') {
            return $this->youtube_thumbnail ?? '';
        }

        return Storage::disk('public')->url($this->thumbnail_path ?? $this->path);
    }

    public function getEmbedUrlAttribute(): ?string
    {
        if ($this->type !== 'video' || $this->source_type !== 'external' || ! $this->url) {
            return null;
        }

        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $this->url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }

        if (preg_match('/vimeo\.com\/(\d+)/', $this->url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
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

    public function getTitleAttribute(): ?string
    {
        return $this->original_name;
    }

    public function getMimeAttribute(): ?string
    {
        return $this->mime_type;
    }

    public function getDisplayUrlAttribute(): ?string
    {
        if ($this->path) {
            return Storage::disk('public')->url($this->path);
        }

        return $this->url;
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function (Media $media) {
            if ($media->type === 'image' && $media->mime_type && str_starts_with($media->mime_type, 'image/')) {
                GenerateMediaThumbnail::dispatch($media);
            }
        });

        static::deleting(function ($media) {
            if ($media->path) {
                Storage::disk('public')->delete($media->path);
            }
            if ($media->thumbnail_path) {
                Storage::disk('public')->delete($media->thumbnail_path);
            }
        });
    }
}
