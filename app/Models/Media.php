<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';
    protected $fillable = ['path', 'thumbnail_path', 'original_name', 'mime_type', 'size'];

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
                    'title' => $model->title,
                ] : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    public function getThumbnailUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->thumbnail_path ?? $this->path);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function (Media $media) {
            if (str_starts_with($media->mime_type, 'image/')) {
                \App\Jobs\GenerateMediaThumbnail::dispatch($media);
            }
        });

        static::deleting(function ($media) {
            Storage::disk('public')->delete($media->path);

            if ($media->thumbnail_path) {
                Storage::disk('public')->delete($media->thumbnail_path);
            }
        });
    }
}