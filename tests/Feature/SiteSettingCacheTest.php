<?php

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renvoie la meme instance pour deux appels consecutifs', function () {
    SiteSetting::create([]);

    expect(SiteSetting::current())->toBe(SiteSetting::current());
});

it('voit une modification des reglages dans la meme requete', function () {
    SiteSetting::create(['hero_title' => 'Avant']);
    expect(SiteSetting::current()->hero_title)->toBe('Avant');

    SiteSetting::first()->update(['hero_title' => 'Apres']);

    expect(SiteSetting::current()->hero_title)->toBe('Apres');
});

it('ne renvoie pas une ligne supprimee', function () {
    SiteSetting::create(['hero_title' => 'Ancien']);
    $ancien = SiteSetting::current();
    $ancien->delete();

    $nouveau = SiteSetting::current();

    expect($nouveau->exists)->toBeTrue()
        ->and($nouveau->id)->not->toBe($ancien->id);
});
