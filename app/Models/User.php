<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    /** @return HasMany<Article, $this> */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /** @return HasMany<Page, $this> */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    /**
     * Vrai si ce compte est auteur d'au moins un article ou une page :
     * le supprimer effacerait ce contenu (cascade en base).
     */
    public function ownsContent(): bool
    {
        return $this->articles()->exists() || $this->pages()->exists();
    }

    /**
     * Vrai si cet utilisateur est Super Admin et qu'aucun autre compte ne l'est :
     * le supprimer ou lui retirer ce role laisserait le site sans administrateur.
     */
    public function isLastSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SuperAdmin)
            && ! static::role(UserRole::SuperAdmin)->whereKeyNot($this->getKey())->exists();
    }
}
