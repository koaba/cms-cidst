<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactAttachment extends Model
{
    protected $fillable = ['path', 'original_name', 'mime', 'size'];
}
