<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactMessage extends Model
{
    protected $fillable = ['name', 'email', 'subject', 'message', 'attachment_link'];

    protected $casts = [
        'read_at' => 'datetime',
        'alerted_at' => 'datetime',
    ];

    /** @return HasMany<ContactAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(ContactAttachment::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
