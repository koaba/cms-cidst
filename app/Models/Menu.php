<?php

namespace App\Models;

use App\Support\SafeUrl;
use App\Traits\HasOrderedMediaCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class Menu extends Model
{
    use HasFactory, HasOrderedMediaCollection;

    public const MAIN = 'main';

    /** Emplacements possibles et leur libelle (l'ordre est celui de l'administration). */
    public const LOCATIONS = [
        'main' => 'Menu principal',
        'footer' => 'Pied de page',
        'legal' => 'Mentions légales',
    ];

    protected $fillable = ['label', 'target', 'order', 'is_active', 'parent_id', 'location'];

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
     * Elements actifs de chaque emplacement, en une seule requete :
     * ['main' => arbre (enfants charges), 'footer' => liste, 'legal' => liste].
     * Un element n'apparait que s'il est actif et, dans le menu principal, que son parent l'est aussi.
     * Le pied de page et les mentions legales sont plats : seuls les elements sans parent sont retenus.
     *
     * @return array<string, Collection>
     */
    public static function activeByLocation(): array
    {
        $all = static::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        $byParent = $all->where('location', self::MAIN)->groupBy(fn (Menu $menu) => $menu->parent_id ?? 0);

        $attach = function (Collection $nodes) use (&$attach, $byParent): Collection {
            foreach ($nodes as $node) {
                $node->setRelation('children', $attach($byParent->get($node->id, new Collection)));
            }

            return $nodes;
        };

        $result = [self::MAIN => $attach($byParent->get(0, new Collection))->values()];

        foreach (array_keys(self::LOCATIONS) as $location) {
            if ($location !== self::MAIN) {
                $result[$location] = $all->where('location', $location)->whereNull('parent_id')->values();
            }
        }

        return $result;
    }

    /** Arbre public du menu principal : chaque noeud a sa relation `children` deja chargee. */
    public static function activeTree(): Collection
    {
        return static::activeByLocation()[self::MAIN];
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

        return SafeUrl::sanitize($this->target);
    }

    /** Adresse complete http(s) : le lien s'ouvre alors dans un nouvel onglet. */
    public function getIsExternalAttribute(): bool
    {
        return ! Route::has($this->target) && preg_match('~^https?://~i', $this->resolved_url) === 1;
    }
}
