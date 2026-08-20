<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use App\Traits\HasOrderedMediaCollection;

class Menu extends Model
{
    use HasOrderedMediaCollection;

    protected $fillable = ['label', 'target', 'order', 'is_active', 'parent_id'];
    protected $casts = ['is_active' => 'boolean'];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
    }

    /**
     * Relation media polymorphique partagee (voir §3.1 doc technique).
     * Ne jamais Storage::delete() a la main sur un media : passer par
     * HasOrphanMediaCleanup::detachAndPruneOrphanMedia().
     */
    public function media()
    {
        return $this->morphToMany(Media::class, 'mediable')
            ->withPivot('order')
            ->orderByPivot('order');
    }

    public function descendantIds(): array
    {
        $ids = [];
        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->descendantIds());
        }
        return $ids;
    }

    public function depth(): int
    {
        $depth = 0;
        $current = $this;
        while ($current->parent_id !== null) {
            $depth++;
            $current = $current->parent;
        }
        return $depth;
    }

    public function getResolvedUrlAttribute(): string
    {
        if (Route::has($this->target)) {
            return route($this->target);
        }
        return $this->target;
    }
}