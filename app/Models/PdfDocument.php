<?php

namespace App\Models;

use App\Traits\HasOrderedMediaCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PdfDocument extends Model
{
    use HasFactory, HasOrderedMediaCollection;

    protected $fillable = ['title', 'slug', 'description', 'pdf_category_id'];

    public function category()
    {
        return $this->belongsTo(PdfCategory::class, 'pdf_category_id');
    }

    public function media()
    {
        return $this->morphToMany(Media::class, 'mediable')
            ->withPivot('order')
            ->orderByPivot('order');
    }

    public function pdfs()
    {
        return $this->media()->where('mime_type', 'application/pdf');
    }

    public function publicUrl(): string
    {
        return route('documents.show', $this);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            $baseSlug = Str::slug($document->title);
            $slug = $baseSlug;
            $counter = 1;
            while (static::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }
            $document->slug = $slug;
        });
    }
}