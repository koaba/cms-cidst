<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Menu;
use App\Models\NewsTicker;
use App\Models\Page;
use App\Models\PdfCategory;
use App\Models\PdfDocument;
use App\Models\Slider;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => 'Super Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super Admin');
});

// Chaque fabrique recoit un marqueur unique : le voir a l'ecran prouve que la bonne ressource est rendue.
$resources = [
    'articles' => fn (string $m) => Article::factory()->create(['title' => $m]),
    'pages' => fn (string $m) => Page::factory()->create(['title' => $m]),
    'sliders' => fn (string $m) => Slider::factory()->create(['title' => $m]),
    'news-tickers' => fn (string $m) => NewsTicker::factory()->create(['content' => $m]),
    'menus' => fn (string $m) => Menu::factory()->create(['label' => $m]),
    'categories' => fn (string $m) => Category::factory()->create(['name' => $m]),
    'pdf-categories' => fn (string $m) => PdfCategory::factory()->create(['name' => $m]),
    'pdf-documents' => fn (string $m) => PdfDocument::factory()->create(['title' => $m]),
];

foreach ($resources as $routePrefix => $factory) {
    it("affiche la page de creation pour {$routePrefix} avec son formulaire d'envoi", function () use ($routePrefix) {
        $this->actingAs($this->admin)->get(route("admin.{$routePrefix}.create"))
            ->assertOk()
            ->assertSee('action="'.route("admin.{$routePrefix}.store").'"', false);
    });

    it("affiche la page d'edition pour {$routePrefix} avec les donnees de l'element", function () use ($routePrefix, $factory) {
        $instance = $factory('Marqueur Edition Unique');

        $this->actingAs($this->admin)->get(route("admin.{$routePrefix}.edit", $instance))
            ->assertOk()
            ->assertSee('Marqueur Edition Unique');
    });

    it("affiche la page d'index pour {$routePrefix} avec l'element existant", function () use ($routePrefix, $factory) {
        $factory('Marqueur Index Unique');

        $this->actingAs($this->admin)->get(route("admin.{$routePrefix}.index"))
            ->assertOk()
            ->assertSee('Marqueur Index Unique');
    });
}

it('affiche la page de creation utilisateur', function () {
    $this->actingAs($this->admin)->get(route('admin.users.create'))
        ->assertOk()
        ->assertSee('action="'.route('admin.users.store').'"', false);
});

it("affiche la page d'edition utilisateur", function () {
    $other = User::factory()->create(['email' => 'marqueur.unique@example.test']);

    $this->actingAs($this->admin)->get(route('admin.users.edit', $other))
        ->assertOk()
        ->assertSee('marqueur.unique@example.test');
});
