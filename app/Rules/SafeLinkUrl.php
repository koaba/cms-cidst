<?php

namespace App\Rules;

use App\Support\SafeUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Refuse les liens qui executent du code ou quittent le site sans le dire (javascript:, data:, //hote...).
 */
class SafeLinkUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! SafeUrl::isSafe($value)) {
            $fail("Le lien doit \u{ea}tre une adresse http ou https, un chemin du site (par exemple /contact), une ancre (#haut), ou un lien mailto: ou tel:.");
        }
    }
}
