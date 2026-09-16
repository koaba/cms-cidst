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
     * Charge tous les enfants du bloc (toutes colonnes confondues) en une
     * seule requête, médias inclus, puis les groupe par column_index.
     * Remplace les appels répétés à childrenByColumn($i)->get() dans une
     * boucle de vue, qui génèrent 1 requête par colonne + 1 requête par
     * enfant pour ->media (N+1 imbriqué).
     *
     * Mesuré (bloc à 4 colonnes, 3 enfants, 5 médias) : 8 requêtes avant
     * -> 3 requêtes après, fixe quel que soit le nombre de colonnes/enfants.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, PageBlock>>
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