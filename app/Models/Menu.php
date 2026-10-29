<?php

namespace App\Models;

use App\Traits\HasOrderedMediaCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class Menu extends Model
{
    use HasFactory, HasOrderedMediaCollection;

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

    /**
     * Arbre public du menu : une seule requete, tous niveaux confondus.
     * Un element n'apparait que s'il est actif et que son parent l'est aussi.
     * Chaque noeud a sa relation `children` deja chargee (pas de requete a l'affichage).
     */
    public static function activeTree(): Collection
    {
        $byParent = static::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Menu $menu) => $menu->parent_id ?? 0);

        $attach = function (Collection $nodes) use (&$attach, $byParent): Collection {
            foreach ($nodes as $node) {
                $node->setRelation('children', $attach($byParent->get($node->id, new Collection)));
            }

            return $nodes;
        };

        return $attach($byParent->get(0, new Collection))->values();
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
