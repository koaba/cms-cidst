<?php

use App\Models\Article;
use App\Models\Page;

it('affiche la liste des pages statiques avec un statut 200', function () {
    Page::factory()->create(['is_published' => true]);

    $response = $this->get(route('pages.index'));

    $response->assertOk();
});

it('affiche la liste des pages meme sans page publiee', function () {
    Page::factory()->create(['is_published' => false]);

    $response = $this->get(route('pages.index'));

    $response->assertOk();
});

it('affiche la page de recherche sans parametre', function () {
    $response = $this->get(route('search'));

    $response->assertOk();
});

it('trouve un article publie correspondant a la recherche', function () {
    Article::factory()->create([
        'title' => 'Conférence exceptionnelle sur le climat',
        'is_published' => true,
    ]);

    $response = $this->get(route('search', ['q' => 'climat']));

    $response->assertOk();
    $response->assertSee('Conférence exceptionnelle sur le climat');
});

it('ne trouve pas un article non publie correspondant a la recherche', function () {
    Article::factory()->create([
        'title' => 'Article secret non publie',
        'is_published' => false,
    ]);

    $response = $this->get(route('search', ['q' => 'secret']));

    $response->assertOk();
    $response->assertDontSee('Article secret non publie');
});

it('trouve une page publiee correspondant a la recherche', function () {
    Page::factory()->create([
        'title' => 'Mentions légales complètes',
        'is_published' => true,
    ]);

    $response = $this->get(route('search', ['q' => 'mentions']));

    $response->assertOk();
    $response->assertSee('Mentions légales complètes');
});

it('rejette une recherche trop longue', function () {
    $response = $this->get(route('search', ['q' => str_repeat('a', 300)]));

    $response->assertStatus(302);
});