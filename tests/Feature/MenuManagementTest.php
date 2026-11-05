<?php

use App\Models\Menu;

beforeEach(function () {
    $this->admin = adminUser();

    $this->makeMenu = fn (array $attributes = []) => Menu::create([
        'label' => 'Accueil', 'target' => '/', 'order' => 0, 'is_active' => true, ...$attributes,
    ]);
});

it('cree un element de menu avec les valeurs par defaut', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.menus.store'), ['label' => 'Contact', 'target' => '/contact'])
        ->assertRedirect(route('admin.menus.index'));

    $menu = Menu::where('label', 'Contact')->first();

    expect($menu->target)->toBe('/contact')
        ->and($menu->order)->toEqual(0)
        ->and($menu->is_active)->toBeFalse()
        ->and($menu->parent_id)->toBeNull();
});

it('cree un sous-menu actif avec son ordre', function () {
    $parent = ($this->makeMenu)();

    $this->actingAs($this->admin)
        ->post(route('admin.menus.store'), [
            'label' => 'Equipe', 'target' => '/equipe', 'order' => 3, 'is_active' => '1', 'parent_id' => $parent->id,
        ])
        ->assertRedirect(route('admin.menus.index'));

    $menu = Menu::where('label', 'Equipe')->first();

    expect($menu->order)->toEqual(3)
        ->and($menu->is_active)->toBeTrue()
        ->and($menu->parent_id)->toEqual($parent->id);
});

it('refuse un element de menu invalide', function (array $payload, string $field) {
    $this->actingAs($this->admin)
        ->post(route('admin.menus.store'), $payload)
        ->assertSessionHasErrors($field);

    expect(Menu::count())->toBe(0);
})->with([
    'sans libelle' => [['target' => '/x'], 'label'],
    'sans cible' => [['label' => 'X'], 'target'],
    'parent inexistant' => [['label' => 'X', 'target' => '/x', 'parent_id' => 999], 'parent_id'],
    'ordre non entier' => [['label' => 'X', 'target' => '/x', 'order' => 'abc'], 'order'],
]);

it('modifie un element de menu et le desactive quand la case est decochee', function () {
    $menu = ($this->makeMenu)();

    $this->actingAs($this->admin)
        ->put(route('admin.menus.update', $menu), ['label' => 'Nouveau', 'target' => '/nouveau', 'order' => 2])
        ->assertRedirect(route('admin.menus.index'));

    $menu->refresh();

    expect($menu->label)->toBe('Nouveau')
        ->and($menu->target)->toBe('/nouveau')
        ->and($menu->order)->toEqual(2)
        ->and($menu->is_active)->toBeFalse();
});

it('interdit qu un menu soit son propre parent', function () {
    $menu = ($this->makeMenu)();

    $this->actingAs($this->admin)
        ->put(route('admin.menus.update', $menu), ['label' => 'Accueil', 'target' => '/', 'parent_id' => $menu->id])
        ->assertSessionHasErrors('parent_id');

    expect($menu->fresh()->parent_id)->toBeNull();
});

it('interdit qu un menu devienne enfant de son descendant', function () {
    $root = ($this->makeMenu)();
    $child = ($this->makeMenu)(['label' => 'Enfant', 'parent_id' => $root->id]);

    $this->actingAs($this->admin)
        ->put(route('admin.menus.update', $root), ['label' => 'Accueil', 'target' => '/', 'parent_id' => $child->id])
        ->assertSessionHasErrors('parent_id');

    expect($root->fresh()->parent_id)->toBeNull();
});
