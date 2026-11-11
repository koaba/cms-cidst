<?php

use App\Models\SiteSetting;

it('transmet au navigateur les limites pour reduire les photos', function () {
    SiteSetting::current()->update(['contact_images_enabled' => true, 'contact_images_max_mb' => 4]);

    $this->get('/contact')->assertOk()
        ->assertSee('data-max-side="3000"', false)
        ->assertSee('data-max-bytes="4194304"', false)
        ->assertSee('id="images_status"', false);
});

it('suit la limite de cote configuree', function () {
    SiteSetting::current()->update(['contact_images_enabled' => true]);
    config(['contact.max_side' => 2500]);

    $this->get('/contact')->assertOk()->assertSee('data-max-side="2500"', false);
});

it('n affiche aucun element de reduction quand les images sont desactivees', function () {
    $this->get('/contact')->assertOk()
        ->assertDontSee('data-max-side', false)
        ->assertDontSee('images_status', false);
});

it('declare le script de reduction comme entree Vite (sinon la page contact plante en production)', function () {
    expect(file_exists(resource_path('js/public/contact-images.js')))->toBeTrue()
        ->and(file_get_contents(base_path('vite.config.js')))->toContain("'resources/js/public/contact-images.js'")
        ->and(file_get_contents(resource_path('views/public/contact.blade.php')))->toContain("@vite('resources/js/public/contact-images.js')");
});
