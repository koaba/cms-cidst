<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
class Menu extends Model
{
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
     * Retourne les IDs de tous les descendants (enfants, petits-enfants, etc.)
     * de ce menu, recursivement. Utilise pour eviter les boucles de hierarchie.
     */
    public function descendantIds(): array
    {
        $ids = [];
        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->descendantIds());
        }
        return $ids;
    }

    /**
     * Calcule la profondeur de ce menu dans la hierarchie (0 = premier niveau).
     */
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

    /**
     * Resout la cible : si c'est un nom de route existant, genere l'URL via route().
     * Sinon, retourne la valeur telle quelle (URL brute ou externe).
     */
    public function getResolvedUrlAttribute(): string
    {
        if (Route::has($this->target)) {
            return route($this->target);
        }
        return $this->target;
    }
}