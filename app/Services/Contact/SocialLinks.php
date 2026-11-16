<?php

namespace App\Services\Contact;

use App\Models\SiteSetting;
use App\Support\SafeUrl;

/**
 * Reseaux sociaux du pied de page : Facebook, YouTube, LinkedIn, dans cet ordre.
 *
 * Facebook vient de SiteContact (source unique) ; YouTube et LinkedIn sont geres ici. Meme
 * interrupteur que les coordonnees (footer_show_contacts). Seules les adresses http(s) sortent.
 * WhatsApp reste une coordonnee (SiteContact::whatsapp) : il n'est pas repete ici.
 * Une colonne absente du modele en cache (avant cache:clear) compte comme « vide ».
 */
final class SocialLinks
{
    private readonly SiteContact $contact;

    public function __construct(private readonly SiteSetting $settings, ?SiteContact $contact = null)
    {
        $this->contact = $contact ?? new SiteContact($settings);
    }

    public static function current(): self
    {
        return new self(SiteSetting::current());
    }

    /** @return list<array{key: string, label: string, href: string}> */
    public function all(): array
    {
        $candidates = [
            'facebook' => ['Facebook', $this->contact->facebook()],
            'youtube' => ['YouTube', $this->httpUrl($this->settings->youtube_url)],
            'linkedin' => ['LinkedIn', $this->httpUrl($this->settings->linkedin_url)],
        ];

        $links = [];

        foreach ($candidates as $key => [$label, $href]) {
            if ($href !== null) {
                $links[] = ['key' => $key, 'label' => $label, 'href' => $href];
            }
        }

        return $links;
    }

    public function has(): bool
    {
        return $this->all() !== [];
    }

    private function httpUrl(?string $value): ?string
    {
        $url = trim((string) $value);

        if (! $this->contact->shown() || $url === '' || ! SafeUrl::isSafe($url)) {
            return null;
        }

        return preg_match('#^https?://#i', $url) === 1 ? $url : null;
    }
}
