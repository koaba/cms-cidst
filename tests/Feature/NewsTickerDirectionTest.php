<?php

use App\Models\SiteSetting;
use App\Models\User;
use App\View\Components\NewsTicker as NewsTickerComponent;
use Spatie\Permission\Models\Role;

it('utilise horizontal par defaut', function () {
    $component = new NewsTickerComponent();
    expect($component->direction)->toBe('horizontal');
});

it('utilise la direction configuree dans les reglages du site', function () {
    SiteSetting::current()->update(['news_ticker_direction' => 'vertical']);

    $component = new NewsTickerComponent();
    expect($component->direction)->toBe('vertical');
});

it('permet a un Super Admin de changer la direction du bandeau', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
        'hero_title' => 'Titre existant',
        'news_ticker_direction' => 'vertical',
    ]);

    $response->assertRedirect(route('admin.settings.edit'));
    expect(SiteSetting::current()->news_ticker_direction)->toBe('vertical');
});

it('rejette une direction invalide', function () {
    Role::create(['name' => 'Super Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
        'hero_title' => 'Titre existant',
        'news_ticker_direction' => 'diagonal',
    ]);

    $response->assertSessionHasErrors('news_ticker_direction');
});