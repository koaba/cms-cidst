<?php

namespace App\Models;

use App\Jobs\GenerateMediaThumbnail;
use App\Jobs\GenerateMediaVariants;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = ['path', 'thumbnail_path', 'original_name', 'mime_type', 'size', 'type', 'source_type', 'url', 'apply_watermark', 'width', 'height', 'variants'];

    protected $casts = [
        'apply_watermark' => 'boolean',
        'width' => 'integer',
        'height' => 'integer',
        'variants' => 'array',
    ];

    public function mediables()
    {
        return $this->hasMany(Mediable::class);
    }

    /** Usages calcules par preloadUsages() ; null tant qu'aucun prechargement n'a eu lieu. */
    protected ?array $preloadedUsages = null;

    public function usages(): array
    {
        if ($this->preloadedUsages !== null) {
            return $this->preloadedUsages;
        }

        return $this->mediables
            ->map(function ($pivot) {
                $model = class_exists($pivot->mediable_type)
                    ? $pivot->mediable_type::find($pivot->mediable_id)
                    : null;

                return self::usageOf($pivot->mediable_type, $model);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Calcule les usages de toute une collection en une requete par type de
     * contenu lie (au lieu d'un find() par pivot). usages() renvoie ensuite
     * le resultat precharge.
     *
     * @param  iterable<Media>  $media
     */
    public static function preloadUsages(iterable $media): void
    {
        $media = EloquentCollection::make($media)->loadMissing('mediables');
        $pivots = $media->flatMap->mediables;

        $models = [];
        foreach ($pivots->groupBy('mediable_type') as $type => $group) {
            if (! class_exists($type) || ! is_subclass_of($type, Model::class)) {
                continue;
            }

            $instance = new $type;
            $models[$type] = $type::query()
                ->whereIn($instance->getKeyName(), $group->pluck('mediable_id')->unique()->all())
                ->get()
                ->keyBy(fn (Model $model) => $model->getKey());
        }

        foreach ($media as $item) {
            $item->preloadedUsages = $item->mediables
                ->map(fn ($pivot) => self::usageOf($pivot->mediable_type, $models[$pivot->mediable_type][$pivot->mediable_id] ?? null))
                ->filter()
                ->values()
                ->all();
        }
    }

    private static function usageOf(string $type, ?Model $model): ?array
    {
        return $model ? [
            'type' => class_basename($type),
            'title' => $model->title ?? $model->name ?? $model->label ?? '(sans titre)',
        ] : null;
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

    public function getSrcsetAttribute(): string
    {
        $disk = Storage::disk('public');

        $candidates = collect($this->variants ?? [])
            ->sortKeys()
            ->map(fn ($path, $width) => $disk->url($path).' '.$width.'w');

        if ($candidates->isNotEmpty() && $this->width) {
            $candidates->push($this->display_url.' '.$this->width.'w');
        }

        return $candidates->implode(', ');
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function (Media $media) {
            if ($media->type === 'image' && $media->mime_type && str_starts_with($media->mime_type, 'image/')) {
                GenerateMediaThumbnail::dispatch($media);
                GenerateMediaVariants::dispatch($media);
            }
        });

        static::deleting(function ($media) {
            if ($media->path) {
                Storage::disk('public')->delete($media->path);
            }
            if ($media->thumbnail_path) {
                Storage::disk('public')->delete($media->thumbnail_path);
            }
            foreach ($media->variants ?? [] as $variantPath) {
                Storage::disk('public')->delete($variantPath);
            }
        });
    }
}
