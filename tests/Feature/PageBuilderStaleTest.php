<?php

use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Queue::fake();
    Role::firstOrCreate(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
    $this->page = Page::factory()->create();
    $this->texte = $this->page->blocks()->create(['type' => 'texte', 'data' => ['title' => null, 'content' => 'A'], 'order' => 0]);
});

function staleVersion($test): string
{
    $version = null;

    $test->actingAs($test->admin)
        ->get(route('admin.pages.builder.show', $test->page))
        ->assertOk()
        ->assertViewHas('builder', function (array $builder) use (&$version) {
            $version = $builder['version'] ?? null;

            return true;
        });

    expect($version)->toBeString();

    return $version;
}

function staleSave($test, array $payload)
{
    return $test->actingAs($test->admin)->postJson(route('admin.pages.builder.save', $test->page), $payload);
}

it('expose une version de la page dans le builder', function () {
    expect(staleVersion($this))->not->toBe('');
});

it('accepte la sauvegarde avec la version courante et renvoie une nouvelle version', function () {
    $v1 = staleVersion($this);

    $response = staleSave($this, [
        'blocks' => [['id' => $this->texte->id, 'type' => 'texte', 'data' => ['title' => null, 'content' => 'B']]],
        'version' => $v1,
    ])->assertOk();

    expect($response->json('version'))->toBeString()->not->toBe($v1);
    expect($this->texte->fresh()->data['content'])->toBe('B');

    staleSave($this, [
        'blocks' => [['id' => $this->texte->id, 'type' => 'texte', 'data' => ['title' => null, 'content' => 'C']]],
        'version' => $response->json('version'),
    ])->assertOk();

    expect($this->texte->fresh()->data['content'])->toBe('C');
});

it('refuse avec 409 une sauvegarde sur une version perimee, sans rien ecrire', function () {
    $v1 = staleVersion($this);
    $other = $this->page->blocks()->create(['type' => 'texte', 'data' => ['content' => 'Autre onglet'], 'order' => 1]);

    staleSave($this, [
        'blocks' => [['id' => $this->texte->id, 'type' => 'texte', 'data' => ['title' => null, 'content' => 'Perime']]],
        'version' => $v1,
    ])->assertStatus(409)->assertJsonPath('code', 'stale');

    expect($other->fresh())->not->toBeNull();
    expect($this->texte->fresh()->data['content'])->toBe('A');
});

it('accepte toujours une sauvegarde sans version (clients anciens)', function () {
    staleSave($this, [
        'blocks' => [['id' => $this->texte->id, 'type' => 'texte', 'data' => ['title' => null, 'content' => 'B']]],
    ])->assertOk();

    expect($this->texte->fresh()->data['content'])->toBe('B');
});

it("change de version quand le contenu d'un bloc editable change", function () {
    $v1 = staleVersion($this);

    $this->texte->update(['data' => ['title' => null, 'content' => 'Modifie ailleurs']]);

    expect(staleVersion($this))->not->toBe($v1);
});

it("change de version quand l'image d'un bloc change", function () {
    $a = Media::factory()->create();
    $b = Media::factory()->create();
    $image = $this->page->blocks()->create(['type' => 'image', 'data' => ['alt' => ''], 'order' => 1]);
    $image->media()->attach($a->id, ['order' => 0]);
    $v1 = staleVersion($this);

    $image->media()->sync([$b->id => ['order' => 0]]);

    expect(staleVersion($this))->not->toBe($v1);
});

it('ne change pas de version quand un bloc verrouille ou un enfant change', function () {
    $cols = $this->page->blocks()->create(['type' => 'colonnes', 'data' => ['column_count' => 2], 'order' => 1]);
    $v1 = staleVersion($this);

    $cols->update(['data' => ['column_count' => 3]]);
    $this->page->blocks()->create(['type' => 'texte', 'data' => ['content' => 'Enfant'], 'order' => 0, 'parent_id' => $cols->id, 'slot_index' => 0]);

    expect(staleVersion($this))->toBe($v1);
});
