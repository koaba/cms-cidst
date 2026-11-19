<?php

use App\Models\Page;
use App\Models\PageBlock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;

// Page Builder de bout en bout : la sauvegarde HTTP du builder est relue sur la page publique,
// cache de rendu deja chaud. Les tests de BlockSyncService ne regardent que la base.

beforeEach(function () {
    Cache::flush();
});

function e2ePublicGet(Page $page): TestResponse
{
    app()->forgetScopedInstances();
    app()->forgetInstance('site-settings.current');

    return test()->get(route('pages.show', $page));
}

function e2eTexte(Page $page, string $content, int $order): PageBlock
{
    return $page->blocks()->create(['type' => 'texte', 'data' => ['title' => null, 'content' => $content], 'order' => $order]);
}

it('affiche sur la page publique ce que le builder ajoute, modifie, reordonne et supprime', function () {
    $page = Page::factory()->create(['is_published' => true]);
    $keep = e2eTexte($page, 'Texte initial', 0);
    e2eTexte($page, 'Bloc a supprimer', 1);

    e2ePublicGet($page)->assertOk()->assertSee('Texte initial')->assertSee('Bloc a supprimer');

    $this->actingAs(adminUser())->postJson(route('admin.pages.builder.save', $page), ['blocks' => [
        ['type' => 'citation', 'data' => ['content' => 'Citation neuve', 'author' => 'Auteur Test']],
        ['id' => $keep->id, 'type' => 'texte', 'data' => ['title' => 'Titre builder', 'content' => 'Texte du builder']],
        ['type' => 'separateur', 'data' => ['style' => 'epais']],
    ]])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('blocks.0.type', 'citation')
        ->assertJsonPath('blocks.1.id', $keep->id);

    expect($page->blocks()->orderBy('order')->pluck('type')->all())->toBe(['citation', 'texte', 'separateur']);

    e2ePublicGet($page)->assertOk()
        ->assertSeeInOrder(['Citation neuve', 'Titre builder', 'Texte du builder'])
        ->assertSee('Auteur Test')
        ->assertSee('<hr class="border-t-4', false)
        ->assertDontSee('Texte initial')
        ->assertDontSee('Bloc a supprimer');
});

it('laisse la page publique intacte quand Publication supprime sans code, et la met a jour avec le bon code', function () {
    setDeleteCode('4242');

    $page = Page::factory()->create(['is_published' => true]);
    $keep = e2eTexte($page, 'Texte conserve', 0);
    e2eTexte($page, 'Bloc condamne', 1);

    e2ePublicGet($page)->assertOk()->assertSee('Texte conserve')->assertSee('Bloc condamne');

    $publication = publicationUser();
    $payload = ['blocks' => [['id' => $keep->id, 'type' => 'texte', 'data' => ['title' => null, 'content' => 'Texte conserve']]]];

    $this->actingAs($publication)->postJson(route('admin.pages.builder.save', $page), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('delete_code');

    e2ePublicGet($page)->assertOk()->assertSee('Bloc condamne');

    $this->actingAs($publication)->postJson(route('admin.pages.builder.save', $page), $payload + ['delete_code' => '4242'])
        ->assertOk();

    e2ePublicGet($page)->assertOk()->assertSee('Texte conserve')->assertDontSee('Bloc condamne');
});
