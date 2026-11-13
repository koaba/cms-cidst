<?php

namespace App\View\Components;

use App\Models\SiteSetting;
use App\Services\Contact\SiteContact;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Pied de page public. Les réglages viennent de SiteSetting (cache invalidé à l'enregistrement) ;
 * une colonne absente du modèle en cache (avant cache:clear) compte comme « affiché ».
 * Les coordonnées viennent de SiteContact (source unique, partagée avec la page Contact et le JSON-LD).
 */
class Footer extends Component
{
    public SiteSetting $settings;

    private SiteContact $contact;

    public function __construct(?SiteSetting $settings = null)
    {
        $this->settings = $settings ?? SiteSetting::current();
        $this->contact = new SiteContact($this->settings);
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
        return $this->contact->shown();
    }

    public function email(): ?string
    {
        return $this->contact->email();
    }

    public function facebook(): ?string
    {
        return $this->contact->facebook();
    }

    /** @return list<string> */
    public function addressLines(): array
    {
        return $this->contact->addressLines();
    }

    /** @return list<string> */
    public function hoursLines(): array
    {
        return $this->contact->hoursLines();
    }

    /** @return list<array{label: string, href: string}> */
    public function phones(): array
    {
        return $this->contact->phones();
    }

    /** @return array{label: string, href: string}|null */
    public function whatsapp(): ?array
    {
        return $this->contact->whatsapp();
    }

    public function hasContacts(): bool
    {
        return $this->contact->has();
    }

    /** Lien « Nous trouver » vers la carte de la page Contact : seulement avec un point GPS valide affiché. */
    public function locationUrl(): ?string
    {
        return $this->contact->coordinates() !== null ? route('contact.show').'#nous-trouver' : null;
    }

    /**
     * Actions du bandeau d'appel : Appeler (premier numero), WhatsApp, puis Ecrire (page Contact).
     * Aucune action sans telephone, WhatsApp ni e-mail valides, ou si les coordonnees sont masquees.
     *
     * @return list<array{label: string, href: string, external: bool}>
     */
    public function ctaActions(): array
    {
        $actions = [];
        $phone = $this->phones()[0] ?? null;
        $whatsapp = $this->whatsapp();

        if ($phone !== null) {
            $actions[] = ['label' => 'Appeler', 'href' => $phone['href'], 'external' => false];
        }

        if ($whatsapp !== null) {
            $actions[] = ['label' => 'WhatsApp', 'href' => $whatsapp['href'], 'external' => true];
        }

        // « Écrire » mène à la page Contact : inutile quand on y est déjà.
        if (($actions !== [] || $this->email() !== null) && ! request()->routeIs('contact.show')) {
            $actions[] = ['label' => 'Écrire', 'href' => route('contact.show'), 'external' => false];
        }

        return $actions;
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
