<?php

namespace App\Models;

use App\Services\Contact\ContactImageStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ContactAttachment extends Model
{
    protected $fillable = ['path', 'original_name', 'mime', 'size'];

    protected static function booted(): void
    {
        static::deleting(function (self $attachment): void {
            Storage::disk(ContactImageStore::DISK)->delete($attachment->path);
        });
    }
}
