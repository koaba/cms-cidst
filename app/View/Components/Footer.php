<?php

namespace App\View\Components;

use App\Models\SiteSetting;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Pied de page public. Les réglages viennent de SiteSetting (cache invalidé à l'enregistrement) ;
 * une colonne absente du modèle en cache (avant cache:clear) compte comme « affiché ».
 */
class Footer extends Component
{
    public SiteSetting $settings;

    public function __construct(?SiteSetting $settings = null)
    {
        $this->settings = $settings ?? SiteSetting::current();
    }

    public function shouldRender(): bool
    {
        return (bool) ($this->settings->show_footer ?? true);
    }

    public function description(): ?string
    {
        return $this->text($this->settings->footer_description);
    }

    public function showContacts(): bool
    {
        return (bool) ($this->settings->footer_show_contacts ?? true);
    }

    public function email(): ?string
    {
        return $this->showContacts() ? $this->text($this->settings->contact_public_email) : null;
    }

    /** Seules les adresses http(s) deviennent un lien : jamais javascript: ni autre schéma. */
    public function facebook(): ?string
    {
        $url = $this->showContacts() ? $this->text($this->settings->facebook_url) : null;

        return $url !== null && preg_match('#^https?://#i', $url) === 1 ? $url : null;
    }

    /** @return list<string> */
    public function addressLines(): array
    {
        return $this->showContacts() ? $this->lines($this->settings->contact_address) : [];
    }

    /** @return list<string> */
    public function hoursLines(): array
    {
        return $this->showContacts() ? $this->lines($this->settings->contact_hours) : [];
    }

    /**
     * Téléphones affichés tels que saisis, avec un lien tel: nettoyé (chiffres et « + » initial).
     * Un numéro de moins de 5 chiffres n'est pas affiché.
     *
     * @return list<array{label: string, href: string}>
     */
    public function phones(): array
    {
        if (! $this->showContacts()) {
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
        $label = $this->showContacts() ? $this->text($this->settings->contact_whatsapp) : null;

        if ($label === null || ! str_starts_with($label, '+')) {
            return null;
        }

        $digits = (string) preg_replace('/\D/', '', $label);

        return strlen($digits) >= 7 ? ['label' => $label, 'href' => 'https://wa.me/'.$digits] : null;
    }

    public function hasContacts(): bool
    {
        return $this->addressLines() !== []
            || $this->phones() !== []
            || $this->whatsapp() !== null
            || $this->hoursLines() !== []
            || $this->email() !== null
            || $this->facebook() !== null;
    }

    /** Texte du bas de page ; {annee} et {site} sont remplacés, vide = « © année Nom du site ». */
    public function copyright(): string
    {
        $text = $this->text($this->settings->footer_copyright) ?? '© {annee} {site}';

        return str_replace(['{annee}', '{site}'], [(string) now()->year, (string) config('app.name', 'CIDST')], $text);
    }

    public function logoSrc(): string
    {
        $path = $this->settings->logo_path;

        return $path ? asset('storage/'.$path) : asset('images/logo-cidst.png');
    }

    /** Dimensions réelles du fichier, pour que le navigateur réserve la place (pas de CLS). */
    public function logoSize(): ?array
    {
        if (! $this->settings->logo_path) {
            return ['width' => 189, 'height' => 144];
        }

        return $this->settings->logo_width && $this->settings->logo_natural_height
            ? ['width' => (int) $this->settings->logo_width, 'height' => (int) $this->settings->logo_natural_height]
            : null;
    }

    public function render(): View
    {
        return view('components.footer');
    }

    /** @return list<string> */
    private function lines(?string $value): array
    {
        $lines = array_map('trim', preg_split('/\R/', (string) $value) ?: []);

        return array_values(array_filter($lines, fn (string $line): bool => $line !== ''));
    }

    /** Chiffres du numéro, avec un « + » conservé seulement en tête. */
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
