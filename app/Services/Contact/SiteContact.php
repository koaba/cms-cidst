<?php

namespace App\Services\Contact;

use App\Models\SiteSetting;

/**
 * Source unique des coordonnees de l'etablissement : adresse, telephones, WhatsApp, horaires,
 * e-mail public, Facebook et point GPS. Le pied de page, la page Contact et le JSON-LD la lisent.
 *
 * Un seul interrupteur (footer_show_contacts) masque tout. Une colonne absente du modele en cache
 * (avant cache:clear) compte comme « affiche » pour l'interrupteur et comme « vide » pour le reste.
 */
class SiteContact
{
    /** Demi-cote de la carte integree, en degres (environ 550 m). */
    private const MAP_PADDING = 0.005;

    public function __construct(private readonly SiteSetting $settings) {}

    public static function current(): self
    {
        return new self(SiteSetting::current());
    }

    public function shown(): bool
    {
        return (bool) ($this->settings->footer_show_contacts ?? true);
    }

    public function email(): ?string
    {
        return $this->shown() ? $this->text($this->settings->contact_public_email) : null;
    }

    /** Seules les adresses http(s) deviennent un lien : jamais javascript: ni autre schema. */
    public function facebook(): ?string
    {
        $url = $this->shown() ? $this->text($this->settings->facebook_url) : null;

        return $url !== null && preg_match('#^https?://#i', $url) === 1 ? $url : null;
    }

    /** @return list<string> */
    public function addressLines(): array
    {
        return $this->shown() ? $this->lines($this->settings->contact_address) : [];
    }

    /** @return list<string> */
    public function hoursLines(): array
    {
        return $this->shown() ? $this->lines($this->settings->contact_hours) : [];
    }

    /**
     * Telephones affiches tels que saisis, avec un lien tel: nettoye (chiffres et « + » initial).
     * Un numero de moins de 5 chiffres n'est pas affiche.
     *
     * @return list<array{label: string, href: string}>
     */
    public function phones(): array
    {
        if (! $this->shown()) {
            return [];
        }

        $phones = [];

        foreach ([$this->settings->contact_phone, $this->settings->contact_phone_2] as $value) {
            $label = $this->text($value);

            if ($label === null) {
                continue;
            }

            $number = $this->dialable($label);

            if (strlen(ltrim($number, '+')) >= 5) {
                $phones[] = ['label' => $label, 'href' => 'tel:'.$number];
            }
        }

        return $phones;
    }

    /**
     * WhatsApp exige le format international : sans « + » initial, le lien wa.me serait faux.
     *
     * @return array{label: string, href: string}|null
     */
    public function whatsapp(): ?array
    {
        $label = $this->shown() ? $this->text($this->settings->contact_whatsapp) : null;

        if ($label === null || ! str_starts_with($label, '+')) {
            return null;
        }

        $digits = (string) preg_replace('/\D/', '', $label);

        return strlen($digits) >= 7 ? ['label' => $label, 'href' => 'https://wa.me/'.$digits] : null;
    }

    /** Au moins un moyen de contact ou un lieu a afficher. Le point GPS n'est pas un moyen de contact. */
    public function has(): bool
    {
        return $this->hasDetails() || $this->email() !== null || $this->facebook() !== null;
    }

    /** Adresse, telephone, WhatsApp ou horaires : ce que montre l'encart de la page Contact (sans e-mail). */
    public function hasDetails(): bool
    {
        return $this->addressLines() !== []
            || $this->phones() !== []
            || $this->whatsapp() !== null
            || $this->hoursLines() !== [];
    }

    /**
     * Point GPS : les deux valeurs presentes et dans les bornes (zero accepte), sinon null.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function coordinates(): ?array
    {
        if (! $this->shown()) {
            return null;
        }

        $lat = $this->settings->map_latitude;
        $lng = $this->settings->map_longitude;

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if (! is_finite($lat) || ! is_finite($lng) || abs($lat) > 90 || abs($lng) > 180) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    public function directionsUrl(): ?string
    {
        $point = $this->coordinates();

        if ($point === null) {
            return null;
        }

        return 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode($this->pair($point['lat'], $point['lng']));
    }

    public function osmEmbedUrl(): ?string
    {
        $point = $this->coordinates();

        if ($point === null) {
            return null;
        }

        $bbox = implode(',', array_map(self::format(...), [
            $point['lng'] - self::MAP_PADDING,
            $point['lat'] - self::MAP_PADDING,
            $point['lng'] + self::MAP_PADDING,
            $point['lat'] + self::MAP_PADDING,
        ]));

        return 'https://www.openstreetmap.org/export/embed.html?bbox='.rawurlencode($bbox)
            .'&layer=mapnik&marker='.rawurlencode($this->pair($point['lat'], $point['lng']));
    }

    private function pair(float $lat, float $lng): string
    {
        return self::format($lat).','.self::format($lng);
    }

    /** Sept decimales au plus, sans zeros superflus : -18.9137, jamais -18.9137000. */
    private static function format(float $value): string
    {
        $text = rtrim(rtrim(number_format($value, 7, '.', ''), '0'), '.');

        return in_array($text, ['', '-0'], true) ? '0' : $text;
    }

    /** @return list<string> */
    private function lines(?string $value): array
    {
        $lines = array_map('trim', preg_split('/\R/', (string) $value) ?: []);

        return array_values(array_filter($lines, fn (string $line): bool => $line !== ''));
    }

    /** Chiffres du numero, avec un « + » conserve seulement en tete. */
    private function dialable(string $value): string
    {
        $number = (string) preg_replace('/[^\d+]/', '', $value);

        return ($number !== '' && $number[0] === '+' ? '+' : '').str_replace('+', '', $number);
    }

    private function text(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
