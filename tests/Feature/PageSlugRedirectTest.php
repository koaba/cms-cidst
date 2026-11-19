<?php

use App\Models\Page;

function psrPage(string $slug, array $extra = []): Page
{
    return Page::factory()->create(array_merge([
        'title' => 'Page '.$slug,
        'slug' => $slug,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ], $extra));
}

it('redirige en 301 un ancien slug vers la nouvelle adresse', function () {
    $page = psrPage('ancien');
    $page->update(['slug' => 'nouveau']);

    $this->get('/pages/ancien')
        ->assertStatus(301)
        ->assertRedirect(route('pages.show', $page->fresh()));
    $this->get('/pages/nouveau')->assertOk();
});

it('envoie tous les anciens slugs directement vers l adresse actuelle', function () {
    $page = psrPage('a');
    $page->update(['slug' => 'b']);
    $page->update(['slug' => 'c']);
    $target = route('pages.show', $page->fresh());

    $this->get('/pages/a')->assertStatus(301)->assertRedirect($target);
    $this->get('/pages/b')->assertStatus(301)->assertRedirect($target);
});

it('retire la redirection quand la page reprend son ancien slug', function () {
    $page = psrPage('a');
    $page->update(['slug' => 'b']);
    $page->update(['slug' => 'a']);

    $this->assertDatabaseMissing('page_slug_redirects', ['old_slug' => 'a']);
    $this->assertDatabaseHas('page_slug_redirects', ['old_slug' => 'b', 'page_id' => $page->id]);
    $this->get('/pages/a')->assertOk();
    $this->get('/pages/b')->assertStatus(301)->assertRedirect(route('pages.show', $page->fresh()));
});

it('ne revele pas la nouvelle adresse d une page non publique', function () {
    $page = psrPage('brouillon', ['is_published' => false]);
    $page->update(['slug' => 'secret']);

    $this->get('/pages/brouillon')->assertNotFound();
});

it('rend 404 pour une page supprimee et pour un slug inconnu', function () {
    $page = psrPage('ancien');
    $page->update(['slug' => 'nouveau']);
    $page->delete();

    $this->get('/pages/ancien')->assertNotFound();
    $this->get('/pages/jamais-existe')->assertNotFound();
});

it('une page actuelle l emporte sur une ancienne redirection du meme slug', function () {
    $first = psrPage('x');
    $first->update(['slug' => 'y']);
    psrPage('x', ['title' => 'Nouvelle page x']);

    $this->get('/pages/x')->assertOk()->assertSee('Nouvelle page x');
});

it('enregistre la redirection quand l admin change le slug', function () {
    $page = psrPage('avant');

    $this->actingAs(adminUser())
        ->put(route('admin.pages.update', $page), [
            'title' => 'Titre',
            'content' => 'Contenu',
            'published_at' => '2026-10-22T14:30',
            'is_published' => '1',
            'slug' => 'apres',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('page_slug_redirects', ['old_slug' => 'avant', 'page_id' => $page->id]);
    $this->get('/pages/avant')->assertStatus(301);
});
