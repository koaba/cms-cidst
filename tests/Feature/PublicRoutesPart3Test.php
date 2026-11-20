<?php

use App\Models\Article;
use App\Models\Page;

it('affiche la liste des pages statiques avec la page publiee', function () {
    Page::factory()->create(['title' => 'Page visible dans la liste', 'is_published' => true]);

    $this->get(route('pages.index'))
        ->assertOk()
        ->assertSee('Page visible dans la liste');
});

it('affiche la liste des pages meme sans page publiee', function () {
    Page::factory()->create(['title' => 'Page non publiee absente', 'is_published' => false]);

    $this->get(route('pages.index'))
        ->assertOk()
        ->assertSee('Aucune page pour le moment.')
        ->assertDontSee('Page non publiee absente');
});

it('affiche la page de recherche sans parametre', function () {
    // Le champ de l'en-tete (type="search") porte aussi name="q" : on cible le champ propre a la page.
    $html = $this->get(route('search'))
        ->assertOk()
        ->assertSee('Entrez un mot')
        ->getContent();

    expect($html)->toMatch('/<input\s+type="text"\s+name="q"/');
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
