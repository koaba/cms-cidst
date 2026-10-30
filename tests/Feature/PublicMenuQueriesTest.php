<?php

use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function menuQueryCount(): int
{
    $count = 0;
    DB::listen(function ($query) use (&$count) {
        if (preg_match('/from [`"]?menus[`"]?/i', $query->sql)) {
            $count++;
        }
    });

    test()->get('/contact')->assertOk();

    return $count;
}

function menuNode(string $label, ?Menu $parent = null, array $attrs = []): Menu
{
    return Menu::factory()->create(array_merge([
        'label' => $label,
        'target' => '/'.str($label)->slug(),
        'parent_id' => $parent?->id,
        'is_active' => true,
    ], $attrs));
}

it('charge le menu public en une seule requete quel que soit le nombre de niveaux', function () {
    foreach (range(1, 3) as $i) {
        $root = menuNode("Racine $i");
        foreach (range(1, 3) as $j) {
            $child = menuNode("Enfant $i-$j", $root);
            foreach (range(1, 2) as $k) {
                menuNode("Petit-enfant $i-$j-$k", $child);
            }
        }
    }

    expect(menuQueryCount())->toBe(1);
});

it('affiche les trois niveaux actifs du menu public', function () {
    $root = menuNode('Racine');
    $child = menuNode('Enfant', $root);
    menuNode('Petit-enfant', $child);

    $this->get('/contact')->assertOk()->assertSee('Racine')->assertSee('Enfant')->assertSee('Petit-enfant');
});

it('masque un element inactif et toute sa descendance', function () {
    $root = menuNode('Racine');
    $off = menuNode('Branche masquee', $root, ['is_active' => false]);
    menuNode('Descendant orphelin', $off);
    menuNode('Enfant visible', $root);

    $this->get('/contact')->assertOk()
        ->assertSee('Enfant visible')
        ->assertDontSee('Branche masquee')
        ->assertDontSee('Descendant orphelin');
});

it('trie chaque niveau par ordre', function () {
    $root = menuNode('Racine');
    menuNode('Second', $root, ['order' => 2]);
    menuNode('Premier', $root, ['order' => 1]);

    $tree = Menu::activeTree();

    expect($tree->first()->children->pluck('label')->all())->toBe(['Premier', 'Second']);
});
