<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Verifie le code de suppression exige des comptes autres que Super Admin.
 * Partage par le middleware confirm.delete et par le Page Builder.
 */
class DeleteCodeVerifier
{
    private const MAX_FAILURES = 5;

    private const DECAY_SECONDS = 900;

    public function isRequiredFor(?User $user): bool
    {
        return ! ($user?->hasRole(UserRole::SuperAdmin) ?? false);
    }

    /** Retourne null si la suppression est autorisee, sinon le message d'erreur a afficher. */
    public function check(Request $request): ?string
    {
        if (! $this->isRequiredFor($request->user())) {
            return null;
        }

        $key = 'confirm-delete:'.($request->user()?->getAuthIdentifier() ?? $request->ip());

        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILURES)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            return "Trop de tentatives. Reessayez dans {$minutes} minute(s).";
        }

        $hash = SiteSetting::current()->delete_code_hash;
        $code = (string) $request->input('delete_code', '');

        if (! $hash || $code === '' || ! Hash::check($code, $hash)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            return 'Code de suppression absent ou incorrect.';
        }

        RateLimiter::clear($key);

        return null;
    }
}
