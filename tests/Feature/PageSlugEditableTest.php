<?php

use App\Models\Page;

function pageSlugPayload(array $extra = []): array
{
    return array_merge([
        'title' => 'Titre',
        'content' => 'Contenu',
        'published_at' => '2026-10-22T14:30',
        'is_published' => '1',
    ], $extra);
}

it('respecte un slug fourni a la creation', function () {
    $page = Page::factory()->create(['title' => 'Titre', 'slug' => 'mon-slug']);

    expect($page->slug)->toBe('mon-slug');
});

it('suffixe un slug fourni deja pris', function () {
    Page::factory()->create(['title' => 'A', 'slug' => 'mon-slug']);
    $second = Page::factory()->create(['title' => 'B', 'slug' => 'mon-slug']);

    expect($second->slug)->toBe('mon-slug-1');
});

it('enregistre le slug modifie a l edition', function () {
    $page = Page::factory()->create(['title' => 'Titre']);

    $this->actingAs(adminUser())
        ->put(route('admin.pages.update', $page), pageSlugPayload(['slug' => 'nouveau-slug']))
        ->assertSessionHasNoErrors();

    expect($page->fresh()->slug)->toBe('nouveau-slug');
});

it('accepte le slug actuel de la page a l edition', function () {
    $page = Page::factory()->create(['title' => 'Titre']);

    $this->actingAs(adminUser())
        ->put(route('admin.pages.update', $page), pageSlugPayload(['slug' => $page->slug]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();
});

it('refuse le slug d une autre page', function () {
    $other = Page::factory()->create(['title' => 'Autre']);
    $page = Page::factory()->create(['title' => 'Titre']);

    $this->actingAs(adminUser())
        ->put(route('admin.pages.update', $page), pageSlugPayload(['slug' => $other->slug]))
        ->assertSessionHasErrors('slug');
});

it('conserve le slug quand le champ est vide', function () {
    $page = Page::factory()->create(['title' => 'Titre']);
    $slug = $page->slug;

    $this->actingAs(adminUser())
        ->put(route('admin.pages.update', $page), pageSlugPayload(['slug' => '']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($page->fresh()->slug)->toBe($slug);
});

it('enregistre le slug fourni via store', function () {
    $this->actingAs(adminUser())
        ->post(route('admin.pages.store'), pageSlugPayload(['slug' => 'slug-du-formulaire']))
        ->assertRedirect(route('admin.pages.index'))
        ->assertSessionHasNoErrors();

    expect(Page::where('title', 'Titre')->value('slug'))->toBe('slug-du-formulaire');
});

it('genere le slug depuis le titre via store quand le champ est vide', function () {
    $this->actingAs(adminUser())
        ->post(route('admin.pages.store'), pageSlugPayload(['title' => 'Mon Titre Special', 'slug' => '']))
        ->assertRedirect(route('admin.pages.index'))
        ->assertSessionHasNoErrors();

    expect(Page::where('title', 'Mon Titre Special')->value('slug'))->toBe('mon-titre-special');
});
