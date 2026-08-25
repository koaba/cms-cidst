<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    // Une seule colonne de temps pertinente ici (viewed_at) ; pas besoin des
    // colonnes created_at/updated_at standard de Laravel.
    public $timestamps = false;

    protected $fillable = [
        'viewable_type',
        'viewable_id',
        'ip_hash',
        'user_agent',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function viewable()
    {
        return $this->morphTo();
    }
}