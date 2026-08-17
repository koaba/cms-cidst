<?php
namespace App\Models;
use App\Traits\HasOrderedMediaCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Category extends Model
{
    use HasOrderedMediaCollection;

    protected $fillable = ['name', 'slug'];
    private const BADGE_COLORS = [
        'bg-blue-100 text-blue-800',
        'bg-yellow-100 text-yellow-800',
        'bg-green-100 text-green-800',
        'bg-purple-100 text-purple-800',
        'bg-pink-100 text-pink-800',
        'bg-orange-100 text-orange-800',
        'bg-teal-100 text-teal-800',
        'bg-red-100 text-red-800',
    ];
    public function badgeColor(): string
    {
        $index = crc32($this->name) % count(self::BADGE_COLORS);
        return self::BADGE_COLORS[$index];
    }
    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }
    public function media()
    {
        return $this->morphToMany(Media::class, 'mediable')
            ->withPivot('order')
            ->orderByPivot('order');
    }
    public function pdfs()
    {
        return $this->media()->where('mime_type', 'application/pdf');
    }
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            $baseSlug = Str::slug($category->name);
            $slug = $baseSlug;
            $counter = 1;
            while (static::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $category->slug = $slug;
        });
    }
}