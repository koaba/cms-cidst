<?php
namespace App\Models;
use App\Traits\HasOrderedMediaCollection;
use Illuminate\Database\Eloquent\Model;
class Diaporama extends Model
{
    use HasOrderedMediaCollection;

    protected $fillable = ['diaporamable_type', 'diaporamable_id', 'title', 'order'];
    public function diaporamable()
    {
        return $this->morphTo();
    }

    public function media()
    {
        return $this->morphToMany(Media::class, 'mediable')
            ->withPivot('order')
            ->orderByPivot('order');
    }
}