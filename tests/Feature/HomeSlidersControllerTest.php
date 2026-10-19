<?php

use App\Models\Slider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it("fournit les sliders de l'accueil depuis le controleur, limites a 5 et ordonnes", function () {
    foreach ([5, 3, 1, 0, 4, 2] as $order) {
        Slider::create(['title' => 'S'.$order, 'image' => 'sliders/s'.$order.'.jpg', 'order' => $order, 'is_active' => true]);
    }

    $this->get('/')
        ->assertOk()
        ->assertViewHas('homeSliders', fn ($sliders) => $sliders?->pluck('title')->all() === ['S0', 'S1', 'S2', 'S3', 'S4']);
});

it('ne requete plus les sliders depuis la vue', function () {
    expect(file_get_contents(resource_path('views/welcome.blade.php')))->not->toContain('Slider::');
});
