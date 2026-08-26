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

$resources = [
    'articles' => fn () => Article::factory()->create(),
    'pages' => fn () => Page::factory()->create(),
    'sliders' => fn () => Slider::factory()->create(),
    'news-tickers' => fn () => NewsTicker::factory()->create(),
    'menus' => fn () => Menu::factory()->create(),
    'categories' => fn () => Category::factory()->create(),
    'pdf-categories' => fn () => PdfCategory::factory()->create(),
    'pdf-documents' => fn () => PdfDocument::factory()->create(),
];

foreach ($resources as $routePrefix => $factory) {
    it("affiche la page de creation pour {$routePrefix}", function () use ($routePrefix) {
        $response = $this->actingAs($this->admin)->get(route("admin.{$routePrefix}.create"));
        $response->assertOk();
    });

    it("affiche la page d'edition pour {$routePrefix}", function () use ($routePrefix, $factory) {
        $instance = $factory();
        $response = $this->actingAs($this->admin)->get(route("admin.{$routePrefix}.edit", $instance));
        $response->assertOk();
    });

    it("affiche la page d'index pour {$routePrefix}", function () use ($routePrefix) {
        $response = $this->actingAs($this->admin)->get(route("admin.{$routePrefix}.index"));
        $response->assertOk();
    });
}

it('affiche la page de creation utilisateur', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.users.create'));
    $response->assertOk();
});

it("affiche la page d'edition utilisateur", function () {
    $other = User::factory()->create();
    $response = $this->actingAs($this->admin)->get(route('admin.users.edit', $other));
    $response->assertOk();
});