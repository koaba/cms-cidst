<?php

use App\Models\SiteSetting;
use App\Services\Theme\ColorConverter;
use App\Services\Theme\ThemeRoles;
use App\Services\Theme\ThemeStyleBuilder;

// Lot 3d-4b : interface des couleurs dans les Reglages (Super Admin). Le formulaire envoie
// les sept roles dans theme_colors[role] ; le garde-fou de contraste (politique A) BLOQUE
// l'enregistrement si le texte principal passe sous 4,5 sur le fond ou sur la surface, et
// seulement AVERTIT pour les autres roles. Le texte sur primaire reste calcule, jamais saisi.

/** Les sept roles aux valeurs par defaut, remplaces par $override. */
function themeColorSettingsFull(array $override = []): array
{
    return [...ThemeRoles::defaults(), ...$override];
}

function themeColorSettingsSave($test, array $colors)
{
    return $test->actingAs(adminUser())
        ->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'theme_colors' => $colors]);
}

it('enregistre les sept couleurs en majuscules', function () {
    themeColorSettingsSave($this, themeColorSettingsFull(['primary' => '#123abc', 'bg' => '#f0f0f0']))
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHasNoErrors();

    expect(SiteSetting::current()->fresh()->theme_colors)
        ->toEqual(themeColorSettingsFull(['primary' => '#123ABC', 'bg' => '#F0F0F0']));
});

it('enregistre seulement les roles envoyes', function () {
    themeColorSettingsSave($this, ['primary' => '#123abc'])->assertSessionHasNoErrors();

    expect(SiteSetting::current()->fresh()->theme_colors)->toEqual(['primary' => '#123ABC']);
});

it('ignore un role laisse vide', function () {
    themeColorSettingsSave($this, ['primary' => '#123ABC', 'bg' => ''])->assertSessionHasNoErrors();

    expect(SiteSetting::current()->fresh()->theme_colors)->toEqual(['primary' => '#123ABC']);
});

it('applique tout de suite les couleurs enregistrees sur le site public', function () {
    $this->get('/')->assertOk()->assertSee('--color-bg-rgb:241 242 244;', false);

    themeColorSettingsSave($this, themeColorSettingsFull([
        'bg' => '#101010', 'surface' => '#181818', 'text' => '#FFFFFF', 'muted' => '#B3B3B3', 'border' => '#574D4C',
    ]))->assertSessionHasNoErrors();

    $this->get('/')->assertOk()
        ->assertSee('--color-bg-rgb:16 16 16;', false)
        ->assertSee('--color-text-rgb:255 255 255;', false);
});

it('refuse une couleur invalide', function (string $value) {
    themeColorSettingsSave($this, themeColorSettingsFull(['primary' => $value]))
        ->assertSessionHasErrors('theme_colors.primary');

    expect(SiteSetting::current()->fresh()->theme_colors)->toBeNull();
})->with(['rouge', '#12345', '#GGGGGG', 'red;}a{', '123456']);

it('refuse un role inconnu', function () {
    themeColorSettingsSave($this, [...themeColorSettingsFull(), 'inconnu' => '#FFFFFF'])
        ->assertSessionHasErrors('theme_colors');

    expect(SiteSetting::current()->fresh()->theme_colors)->toBeNull();
});

it('refuse de saisir le texte sur primaire', function () {
    themeColorSettingsSave($this, [...themeColorSettingsFull(), 'primary_content' => '#FF0000'])
        ->assertSessionHasErrors('theme_colors');

    expect(SiteSetting::current()->fresh()->theme_colors)->toBeNull();
});

it('refuse un theme_colors qui n est pas un tableau', function () {
    $this->actingAs(adminUser())
        ->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'theme_colors' => 'texte'])
        ->assertSessionHasErrors('theme_colors');

    expect(SiteSetting::current()->fresh()->theme_colors)->toBeNull();
});

it('bloque un texte principal illisible sur le fond', function () {
    themeColorSettingsSave($this, themeColorSettingsFull(['text' => '#CCCCCC']))
        ->assertSessionHasErrors('theme_colors.text');

    expect(SiteSetting::current()->fresh()->theme_colors)->toBeNull();
});

it('bloque un texte principal illisible sur la surface', function () {
    // Blanc sur noir est lisible sur le fond, mais pas sur une surface claire.
    themeColorSettingsSave($this, themeColorSettingsFull(['bg' => '#000000', 'text' => '#FFFFFF', 'surface' => '#EEEEEE']))
        ->assertSessionHasErrors('theme_colors.text');

    expect(SiteSetting::current()->fresh()->theme_colors)->toBeNull();
});

it('explique le blocage avec le contraste et le minimum', function () {
    $response = $this->actingAs(adminUser())->putJson(route('admin.settings.update'), ['hero_title' => 'Titre', 'theme_colors' => themeColorSettingsFull(['text' => '#CCCCCC'])]);

    $message = $response->assertStatus(422)->json('errors')['theme_colors.text'][0];

    expect($message)->toContain('Texte principal sur le fond')->toContain('minimum 4,5:1');
});

it('n avertit pas avec les couleurs par defaut', function () {
    themeColorSettingsSave($this, themeColorSettingsFull())
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('warnings');
});

it('avertit sans bloquer quand le texte secondaire est peu lisible', function () {
    themeColorSettingsSave($this, themeColorSettingsFull(['muted' => '#BBBBBB']))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('warnings', fn (array $warnings) => collect($warnings)->contains(fn (string $message) => str_contains($message, 'Texte secondaire')));

    expect(SiteSetting::current()->fresh()->theme_colors['muted'])->toBe('#BBBBBB');
});

it('avertit sans bloquer quand la couleur primaire se voit mal sur la surface', function () {
    themeColorSettingsSave($this, themeColorSettingsFull(['primary' => '#EEEEEE']))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('warnings', fn (array $warnings) => collect($warnings)->contains(fn (string $message) => str_contains($message, 'Couleur primaire')));

    expect(SiteSetting::current()->fresh()->theme_colors['primary'])->toBe('#EEEEEE');
});

it('avertit sans bloquer quand les bordures se confondent avec la surface', function () {
    themeColorSettingsSave($this, themeColorSettingsFull(['border' => '#FFFFFF']))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('warnings', fn (array $warnings) => collect($warnings)->contains(fn (string $message) => str_contains($message, 'Bordures')));
});

it('calcule le texte sur primaire au lieu de le saisir', function () {
    themeColorSettingsSave($this, themeColorSettingsFull(['primary' => '#FFE000']))->assertSessionHasNoErrors();

    $this->get('/')->assertSee('--color-primary-content-rgb:0 0 0;', false);
});

it('resout les roles par les memes couches que le css', function () {
    config(['themes.themes.essai' => ['label' => 'Essai', 'palette' => ['primary' => '#E61919', 'secondary' => '#24335C']]]);
    $builder = new ThemeStyleBuilder(new ColorConverter);

    expect($builder->roles('#AA0000', '#17181A')->color('primary'))->toBe('#AA0000')
        ->and($builder->roles('#AA0000', '#17181A', 'essai')->color('primary'))->toBe('#E61919')
        ->and($builder->roles('#AA0000', '#17181A', 'essai', ['primary' => '#123abc'])->color('primary'))->toBe('#123ABC')
        ->and($builder->roles(null, null)->all())->toBe(ThemeRoles::defaults());
});

it('reinitialise les couleurs aux valeurs par defaut', function () {
    SiteSetting::current()
        ->forceFill(['theme_colors' => ['bg' => '#101010'], 'primary_color' => '#123ABC', 'secondary_color' => '#000000'])
        ->saveQuietly();

    $this->actingAs(adminUser())
        ->delete(route('admin.settings.colors.reset'))
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHas('success');

    $settings = SiteSetting::current()->fresh();

    expect($settings->theme_colors)->toBeNull()
        ->and($settings->primary_color)->toBe('#C41E2A')
        ->and($settings->secondary_color)->toBe('#17181A');

    $this->get('/')->assertSee('--color-primary-rgb:196 30 42;', false)->assertSee('--color-bg-rgb:241 242 244;', false);
});

it('reserve la reinitialisation au Super Admin', function () {
    SiteSetting::current()->forceFill(['theme_colors' => ['bg' => '#101010']])->saveQuietly();

    $this->actingAs(publicationUser())->delete(route('admin.settings.colors.reset'))->assertForbidden();

    expect(SiteSetting::current()->fresh()->theme_colors)->toEqual(['bg' => '#101010']);
});

it('demande une connexion pour reinitialiser les couleurs', function () {
    $this->delete(route('admin.settings.colors.reset'))->assertRedirect(route('login'));
});

it('affiche un champ par role dans les reglages', function () {
    $html = $this->actingAs(adminUser())->get(route('admin.settings.edit'))->assertOk()->getContent();

    foreach (ThemeRoles::keys() as $role) {
        expect($html)->toContain('name="theme_colors['.$role.']"');
    }

    expect($html)->toContain('type="color"')->toContain(route('admin.settings.colors.reset'));
});

it('affiche les couleurs enregistrees dans le formulaire', function () {
    SiteSetting::current()->forceFill(['theme_colors' => ['bg' => '#1A1A1A']])->saveQuietly();

    $html = $this->actingAs(adminUser())->get(route('admin.settings.edit'))->assertOk()->getContent();

    expect($html)->toContain('value="#1A1A1A"')->toContain('value="#C41E2A"');
});

it('garde la saisie refusee dans le formulaire', function () {
    $admin = adminUser();

    $this->actingAs($admin)->from(route('admin.settings.edit'))
        ->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'theme_colors' => themeColorSettingsFull(['primary' => '#12345'])])
        ->assertRedirect(route('admin.settings.edit'));

    $this->actingAs($admin)->get(route('admin.settings.edit'))->assertSee('value="#12345"', false);
});

it('affiche la lisibilite des couleurs enregistrees', function () {
    $html = $this->actingAs(adminUser())->get(route('admin.settings.edit'))->assertOk()->getContent();

    expect($html)->toContain('Texte principal sur le fond')->toContain('Texte secondaire sur la surface');
});

it('affiche les avertissements en attente', function () {
    $this->actingAs(adminUser())
        ->withSession(['warnings' => ['Texte secondaire sur le fond : contraste 1,71:1, minimum 4,5:1.']])
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('Texte secondaire sur le fond : contraste 1,71:1', false);
});
