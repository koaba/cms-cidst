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

    private function text(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
