<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PdfCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function documents()
    {
        return $this->hasMany(PdfDocument::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            $baseSlug = Str::slug($category->name);
            $slug = $baseSlug;
            $counter = 1;

            while (static::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $category->slug = $slug;
        });
    }
}