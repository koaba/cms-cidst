<?php

use App\Models\SiteSetting;

// Lot 3h-1c : latitude et longitude du point GPS, saisies dans /admin/settings.
// Les deux ou aucune ; la virgule decimale est acceptee (saisie francaise).

function mapSettingsSave($test, array $extra = [])
{
    return $test->actingAs(adminUser())->put(route('admin.settings.update'), [
        'hero_title' => 'Titre',
        'show_footer' => '1',
        'footer_show_contacts' => '1',
        ...$extra,
    ]);
}

it('enregistre le point GPS', function () {
    mapSettingsSave($this, ['map_latitude' => '-18.9137', 'map_longitude' => '47.5361'])
        ->assertRedirect()->assertSessionHasNoErrors();

    $settings = SiteSetting::current();

    expect($settings->map_latitude)->toBe(-18.9137)
        ->and($settings->map_longitude)->toBe(47.5361);
});

it('accepte la virgule decimale', function () {
    mapSettingsSave($this, ['map_latitude' => '-18,9137', 'map_longitude' => '47,5361'])
        ->assertRedirect()->assertSessionHasNoErrors();

    expect(SiteSetting::current()->map_latitude)->toBe(-18.9137)
        ->and(SiteSetting::current()->map_longitude)->toBe(47.5361);
});

it('efface le point GPS quand les deux champs sont vides', function () {
    SiteSetting::current()->update(['map_latitude' => -18.9137, 'map_longitude' => 47.5361]);

    mapSettingsSave($this, ['map_latitude' => '', 'map_longitude' => ''])
        ->assertRedirect()->assertSessionHasNoErrors();

    expect(SiteSetting::current()->map_latitude)->toBeNull()
        ->and(SiteSetting::current()->map_longitude)->toBeNull();
});

it('refuse une seule des deux coordonnees', function (array $input, string $missing) {
    mapSettingsSave($this, $input)->assertSessionHasErrors($missing);

    expect(SiteSetting::current()->map_latitude)->toBeNull()
        ->and(SiteSetting::current()->map_longitude)->toBeNull();
})->with([
    'latitude sans longitude' => [['map_latitude' => '-18.9137'], 'map_longitude'],
    'longitude sans latitude' => [['map_longitude' => '47.5361'], 'map_latitude'],
]);

it('refuse un point GPS hors des bornes ou mal forme', function (array $input, string $field) {
    mapSettingsSave($this, $input)->assertSessionHasErrors($field);

    expect(SiteSetting::current()->map_latitude)->toBeNull();
})->with([
    'latitude trop haute' => [['map_latitude' => '91', 'map_longitude' => '47.5361'], 'map_latitude'],
    'latitude trop basse' => [['map_latitude' => '-91', 'map_longitude' => '47.5361'], 'map_latitude'],
    'longitude trop haute' => [['map_latitude' => '-18.9137', 'map_longitude' => '181'], 'map_longitude'],
    'longitude trop basse' => [['map_latitude' => '-18.9137', 'map_longitude' => '-181'], 'map_longitude'],
    'latitude non numerique' => [['map_latitude' => 'abc', 'map_longitude' => '47.5361'], 'map_latitude'],
    'longitude non numerique' => [['map_latitude' => '-18.9137', 'map_longitude' => 'abc'], 'map_longitude'],
    'trop de decimales' => [['map_latitude' => '-18.91370001', 'map_longitude' => '47.5361'], 'map_latitude'],
]);

it('propose les deux champs dans le formulaire des reglages', function () {
    $this->actingAs(adminUser())->get(route('admin.settings.edit'))->assertOk()
        ->assertSee('name="map_latitude"', false)
        ->assertSee('name="map_longitude"', false);
});

it('pre-remplit le formulaire avec le point GPS enregistre', function () {
    SiteSetting::current()->update(['map_latitude' => -18.9137, 'map_longitude' => 47.5361]);

    $this->actingAs(adminUser())->get(route('admin.settings.edit'))->assertOk()
        ->assertSee('value="-18.9137"', false)
        ->assertSee('value="47.5361"', false);
});

it('reserve les champs GPS au Super Admin', function () {
    $this->actingAs(publicationUser())
        ->put(route('admin.settings.update'), ['hero_title' => 'Titre', 'map_latitude' => '-18.9137', 'map_longitude' => '47.5361'])
        ->assertForbidden();

    expect(SiteSetting::current()->map_latitude)->toBeNull();
});
