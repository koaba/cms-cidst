<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisit extends Model
{
    public $timestamps = false;

    protected $fillable = ['path', 'visitor_hash', 'referrer_host', 'device', 'visited_at'];

    protected $casts = ['visited_at' => 'datetime'];
}
