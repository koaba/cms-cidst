<?php

namespace App\Rules;

use App\Support\VideoEmbed;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Exige un lien YouTube ou Vimeo reconnu quand la video vient d'une URL.
 */
class SupportedVideoUrl implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (($this->data['source_type'] ?? null) !== 'url') {
            return;
        }

        if (VideoEmbed::embedUrl(is_string($value) ? $value : null) === null) {
            $fail('Le lien doit etre une URL YouTube ou Vimeo valide.');
        }
    }
}
