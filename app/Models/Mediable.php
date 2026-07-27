<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

class Mediable extends MorphPivot
{
    protected $table = 'mediables';

    protected $fillable = ['media_id', 'mediable_id', 'mediable_type', 'order'];
}