<?php

namespace App\Models;

use App\Traits\HasOrderedMediaCollection;
use App\Traits\HasOrphanMediaCleanup;
use Illuminate\Database\Eloquent\Model;

class PageBlock extends Model
{
    use HasOrderedMediaCollection, HasOrphanMediaCleanup;

    protected $fillable = ['page_id', 'parent_id', 'column_index', 'type', 'data', 'order'];

    protected $casts = [
        'data' => 'array',
        'column_index' => 'integer',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function parent()
    {
        return $this->belongsTo(PageBlock::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(PageBlock::class, 'parent_id')->orderBy('order');
    }

    public function childrenByColumn(int $index)
    {
        return $this->children()->where('column_index', $index);
    }

    /**
     * Charge tous les enfants (+ leurs médias) en une seule requête,
     * regroupés par colonne. À utiliser dans les vues qui itèrent sur
     * plusieurs colonnes, pour éviter le N+1 de childrenByColumn()
     * appelé en boucle. childrenByColumn() reste utile pour cibler un
     * enfant précis (ex. dans le contrôleur).
     */
    public function childrenGroupedByColumn()
    {
        return $this->children()->with('media')->get()->groupBy('column_index');
    }

    public function media()
    {
        return $this->morphToMany(Media::class, 'mediable')
            ->withPivot('order', 'alt', 'caption')
            ->orderByPivot('order');
    }
}