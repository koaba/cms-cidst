<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class Menu extends Model
{
    protected $fillable = ['label', 'target', 'order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    /**
     * Résout la cible : si c'est un nom de route existant, génère l'URL via route().
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