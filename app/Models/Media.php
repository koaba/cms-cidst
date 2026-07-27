<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['path', 'original_name', 'mime_type', 'size'];

    public function mediables()
    {
        return $this->hasMany(Mediable::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($media) {
            Storage::disk('public')->delete($media->path);
        });
    }
}