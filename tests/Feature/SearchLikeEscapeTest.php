<?php

use App\Models\Article;
use App\Models\Media;

beforeEach(function () {
    $this->publie = fn (string $title) => Article::factory()->create([
        'title' => $title,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    // Titres des resultats : le layout public liste deja tous les articles (bandeau, colonne), le HTML ne prouve rien.
    $this->resultats = fn (string $q) => $this->get('/recherche?q='.rawurlencode($q))
        ->assertOk()
        ->viewData('articles')
        ->pluck('title')
        ->all();
});

it('traite le pourcent saisi comme un caractere et non comme un joker', function () {
    ($this->publie)('Remise 50% exacte');
    ($this->publie)('Autre article banal');

    expect(($this->resultats)('%'))->toBe(['Remise 50% exacte']);
});

it('traite le tiret bas saisi comme un caractere et non comme un joker', function () {
    ($this->publie)('Code a_b present');
    ($this->publie)('Code axb voisin');

    expect(($this->resultats)('a_b'))->toBe(['Code a_b present']);
});

it('trouve un titre contenant le caractere d\'echappement', function () {
    ($this->publie)('Alerte! urgente');
    ($this->publie)('Alerte sans point');

    expect(($this->resultats)('e!'))->toBe(['Alerte! urgente']);
});

it('applique la meme regle a la recherche de la mediatheque', function () {
    Queue::fake();
    Media::create(['path' => 'test/a.png', 'original_name' => '100%-logo.png', 'mime_type' => 'image/png', 'size' => 1024]);
    Media::create(['path' => 'test/b.jpg', 'original_name' => 'autre-fichier.jpg', 'mime_type' => 'image/jpeg', 'size' => 1024]);

    $noms = $this->actingAs(adminUser())
        ->get(route('admin.media.index', ['q' => '%']))
        ->assertOk()
        ->viewData('media')
        ->getCollection()
        ->pluck('original_name')
        ->all();

    expect($noms)->toBe(['100%-logo.png']);
});
