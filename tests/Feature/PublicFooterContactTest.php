<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;

// Lot 3h-1b : coordonnees de l'etablissement (adresse, deux telephones, WhatsApp, horaires)
// modifiables dans les Reglages (Super Admin) et affichees dans la colonne « Nous joindre » du
// pied de page, avec l'e-mail et Facebook. Tout est masque par l'option « Afficher les coordonnees ».
// La carte et l'itineraire sont le lot 3h-1c.

function publicFooterContactHtml(): string
{
    return Blade::render('<x-footer />');
}

function publicFooterContactSet(array $attributes): void
{
    SiteSetting::current()->update($attributes);
}

function publicFooterContactSave($test, array $extra = [])
{
    return $test->actingAs(adminUser())->put(route('admin.settings.update'), [
        'hero_title' => 'Titre',
        'show_footer' => '1',
        'footer_show_contacts' => '1',
        ...$extra,
    ]);
}

// ---------------------------------------------------------------- affichage

it('affiche l adresse ligne par ligne, echappee', function () {
    publicFooterContactSet(['contact_address' => "12 rue <b>A</b>\nAntananarivo"]);

    expect(publicFooterContactHtml())
        ->toContain('<address')
        ->toContain('12 rue &lt;b&gt;A&lt;/b&gt;<br>')
        ->toContain('Antananarivo')
        ->not->toContain('<b>A</b>');
});

it('ignore les lignes vides de l adresse', function () {
    publicFooterContactSet(['contact_address' => "A\r\n\r\n  \r\nB"]);

    expect(substr_count(publicFooterContactHtml(), '<br>'))->toBe(1);
});

it('affiche les telephones en liens tel', function () {
    publicFooterContactSet(['contact_phone' => '+261 20 22 000 00', 'contact_phone_2' => '034 12 345 67']);

    expect(publicFooterContactHtml())
        ->toContain('href="tel:+261202200000"')
        ->toContain('+261 20 22 000 00')
        ->toContain('href="tel:0341234567"')
        ->toContain('034 12 345 67');
});

it('nettoie le numero du lien tel', function () {
    publicFooterContactSet(['contact_phone' => '(+261) 20-22.000 00']);

    expect(publicFooterContactHtml())->toContain('href="tel:+261202200000"')->toContain('(+261) 20-22.000 00');
});

it('n affiche pas un telephone trop court ou sans chiffres', function (string $value) {
    publicFooterContactSet(['contact_phone' => $value]);

    expect(publicFooterContactHtml())->not->toContain('tel:')->not->toContain('Nous joindre');
})->with(['abc', '12', '+', '+12']);

it('affiche WhatsApp avec un lien wa.me qui s ouvre dans un nouvel onglet', function () {
    publicFooterContactSet(['contact_whatsapp' => '+261 34 12 345 67']);

    expect(publicFooterContactHtml())
        ->toContain('href="https://wa.me/261341234567" target="_blank" rel="noopener noreferrer"')
        ->toContain('+261 34 12 345 67')
        ->toContain('WhatsApp');
});

it('n affiche pas un WhatsApp trop court meme avec indicatif', function () {
    publicFooterContactSet(['contact_whatsapp' => '+12 345']);

    expect(publicFooterContactHtml())->not->toContain('wa.me');
});

it('affiche un WhatsApp de exactement 7 chiffres', function () {
    publicFooterContactSet(['contact_whatsapp' => '+123 4567']);

    expect(publicFooterContactHtml())->toContain('https://wa.me/1234567');
});

it('le formulaire envoie le champ', function (string $name) {
    $this->actingAs(adminUser())
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('name="'.$name.'"', false);
})->with(['contact_address', 'contact_phone', 'contact_phone_2', 'contact_whatsapp', 'contact_hours']);

it('n affiche pas un WhatsApp sans indicatif international', function () {
    publicFooterContactSet(['contact_whatsapp' => '034 12 345 67']);

    expect(publicFooterContactHtml())->not->toContain('wa.me');
});

it('affiche les horaires ligne par ligne, echappes', function () {
    publicFooterContactSet(['contact_hours' => "Lun-Ven 8h-17h\n<i>Sam</i> 8h-12h"]);

    expect(publicFooterContactHtml())
        ->toContain('Lun-Ven 8h-17h<br>')
        ->toContain('&lt;i&gt;Sam&lt;/i&gt; 8h-12h')
        ->not->toContain('<i>Sam</i>');
});

it('affiche la colonne Nous joindre avec un seul element', function (array $attributes) {
    publicFooterContactSet($attributes);

    expect(publicFooterContactHtml())->toContain('Nous joindre');
})->with([
    'adresse' => [['contact_address' => 'Antananarivo']],
    'telephone' => [['contact_phone' => '0341234567']],
    'whatsapp' => [['contact_whatsapp' => '+261341234567']],
    'horaires' => [['contact_hours' => 'Lun-Ven 8h-17h']],
]);

it('masque toutes les coordonnees quand l option est decochee', function () {
    publicFooterContactSet([
        'contact_address' => 'Rue du Test',
        'contact_phone' => '0341234567',
        'contact_whatsapp' => '+261341234567',
        'contact_hours' => 'Lun-Ven 8h-17h',
        'footer_show_contacts' => false,
    ]);

    expect(publicFooterContactHtml())
        ->not->toContain('Nous joindre')
        ->not->toContain('<address')
        ->not->toContain('Rue du Test')
        ->not->toContain('tel:')
        ->not->toContain('wa.me')
        ->not->toContain('Lun-Ven');
});

it('ordonne les coordonnees de facon stable', function () {
    publicFooterContactSet([
        'contact_address' => 'Rue du Test',
        'contact_phone' => '0341234567',
        'contact_whatsapp' => '+261341234567',
        'contact_hours' => 'Lun-Ven 8h-17h',
        'contact_public_email' => 'documents@cidst.mg',
        'facebook_url' => 'https://facebook.com/cidst',
    ]);

    $html = publicFooterContactHtml();
    $positions = array_map(fn (string $needle) => strpos($html, $needle), [
        'Rue du Test', 'href="tel:', 'href="https://wa.me/', 'Lun-Ven', 'href="mailto:', 'href="https://facebook.com',
    ]);

    $sorted = $positions;
    sort($sorted);

    expect($positions)->each->not->toBeFalse();
    expect($positions)->toBe($sorted);
});

it('accompagne chaque element d une icone svg decorative', function () {
    publicFooterContactSet(['contact_phone' => '0341234567', 'contact_public_email' => 'documents@cidst.mg']);

    $html = publicFooterContactHtml();

    expect(substr_count($html, '<svg'))->toBe(2)
        ->and(substr_count($html, 'aria-hidden="true"'))->toBe(2);
});

it('garde une zone tactile de 44 px sur les liens de contact', function () {
    publicFooterContactSet([
        'contact_phone' => '0341234567',
        'contact_whatsapp' => '+261341234567',
        'contact_public_email' => 'documents@cidst.mg',
    ]);

    expect(publicFooterContactHtml())
        ->toMatch('/href="tel:[^"]*"[^>]*min-h-\[44px\]/')
        ->toMatch('/href="https:\/\/wa\.me\/[^"]*"[^>]*min-h-\[44px\]/')
        ->toMatch('/href="mailto:[^"]*"[^>]*min-h-\[44px\]/');
});

// ---------------------------------------------------------------- reglages

it('cree les colonnes des coordonnees', function () {
    expect(Schema::hasColumns('site_settings', ['contact_address', 'contact_phone', 'contact_phone_2', 'contact_whatsapp', 'contact_hours']))->toBeTrue();
});

it('affiche les champs des coordonnees dans les reglages', function () {
    $this->actingAs(adminUser())->get(route('admin.settings.edit'))->assertOk()
        ->assertSee('id="contact_address"', false)
        ->assertSee('id="contact_phone"', false)
        ->assertSee('id="contact_phone_2"', false)
        ->assertSee('id="contact_whatsapp"', false)
        ->assertSee('id="contact_hours"', false);
});

it('enregistre les coordonnees', function () {
    publicFooterContactSave($this, [
        'contact_address' => "Rue du Test\nAntananarivo",
        'contact_phone' => '+261 20 22 000 00',
        'contact_phone_2' => '034 12 345 67',
        'contact_whatsapp' => '+261 34 12 345 67',
        'contact_hours' => 'Lun-Ven 8h-17h',
    ])->assertSessionHasNoErrors();

    $settings = SiteSetting::current()->fresh();

    expect($settings->contact_address)->toBe("Rue du Test\nAntananarivo")
        ->and($settings->contact_phone)->toBe('+261 20 22 000 00')
        ->and($settings->contact_phone_2)->toBe('034 12 345 67')
        ->and($settings->contact_whatsapp)->toBe('+261 34 12 345 67')
        ->and($settings->contact_hours)->toBe('Lun-Ven 8h-17h');
});

it('accepte les formats usuels de numero', function (string $value) {
    publicFooterContactSave($this, ['contact_phone' => $value])->assertSessionHasNoErrors();
})->with(['+261 20 22 000 00', '034 12 345 67', '(020) 22 000 00', '+261.34.12.345.67', '0341234567']);

it('refuse un telephone invalide', function (string $field, string $value) {
    publicFooterContactSave($this, [$field => $value])->assertSessionHasErrors($field);
})->with(['contact_phone', 'contact_phone_2'])->with(['abc', '12', '+261;drop', '034<script>']);

it('refuse un WhatsApp sans indicatif international', function (string $value) {
    publicFooterContactSave($this, ['contact_whatsapp' => $value])->assertSessionHasErrors('contact_whatsapp');
})->with(['0341234567', '034 12 345 67', '+12', 'abc']);

it('refuse une adresse ou des horaires trop longs', function (string $field) {
    publicFooterContactSave($this, [$field => str_repeat('a', 256)])->assertSessionHasErrors($field);
})->with(['contact_address', 'contact_hours']);

it('garde la saisie refusee dans le formulaire', function () {
    $this->actingAs(adminUser())
        ->from(route('admin.settings.edit'))
        ->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'contact_phone' => 'abc'])
        ->assertSessionHasErrors('contact_phone');

    $this->get(route('admin.settings.edit'))->assertOk()->assertSee('value="abc"', false);
});

it('applique tout de suite les coordonnees sur le site public', function () {
    publicFooterContactSave($this, ['contact_phone' => '+261 20 22 000 00'])->assertSessionHasNoErrors();

    $this->get('/contact')->assertOk()->assertSee('href="tel:+261202200000"', false);
});
