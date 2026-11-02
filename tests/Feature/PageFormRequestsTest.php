<?php

use App\Http\Controllers\Admin\PageController;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Page;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;

it('partage les memes regles de champs entre creation et edition', function () {
    $page = Page::factory()->create();

    $store = (new StorePageRequest)->rules();

    $update = new UpdatePageRequest;
    $update->setRouteResolver(function () use ($update, $page) {
        $route = new Route('PUT', 'admin/pages/{page}', []);
        $route->bind($update);
        $route->setParameter('page', $page);

        return $route;
    });

    expect(array_keys($update->rules()))->toBe(array_keys($store))
        ->and(collect($update->rules())->except('slug')->all())
        ->toEqual(collect($store)->except('slug')->all());
});

it('refuse a la creation un slug deja pris', function () {
    $existante = Page::factory()->create();

    $validator = Validator::make(
        ['title' => 'T', 'content' => 'C', 'published_at' => '2026-10-22T14:30', 'slug' => $existante->slug],
        (new StorePageRequest)->rules(),
    );

    expect($validator->errors()->keys())->toBe(['slug']);
});

it('accepte a l edition le slug de la page elle-meme mais pas celui d une autre', function () {
    $page = Page::factory()->create();
    $autre = Page::factory()->create();

    $request = new UpdatePageRequest;
    $request->setRouteResolver(function () use ($request, $page) {
        $route = new Route('PUT', 'admin/pages/{page}', []);
        $route->bind($request);
        $route->setParameter('page', $page);

        return $route;
    });

    $base = ['title' => 'T', 'content' => 'C', 'published_at' => '2026-10-22T14:30'];

    expect(Validator::make($base + ['slug' => $page->slug], $request->rules())->passes())->toBeTrue()
        ->and(Validator::make($base + ['slug' => $autre->slug], $request->rules())->errors()->keys())->toBe(['slug']);
});

it('branche le controleur admin sur les deux FormRequests', function () {
    $type = fn (string $method) => (new ReflectionMethod(PageController::class, $method))
        ->getParameters()[0]->getType()->getName();

    expect($type('store'))->toBe(StorePageRequest::class)
        ->and($type('update'))->toBe(UpdatePageRequest::class);
});

it('impose les champs obligatoires et les limites de longueur', function () {
    $rules = (new StorePageRequest)->rules();

    $vide = Validator::make([], $rules);
    $trop = Validator::make([
        'title' => str_repeat('a', 256),
        'content' => 'C',
        'published_at' => '2026-10-22T14:30',
        'seo' => ['meta_title' => str_repeat('a', 61), 'meta_description' => str_repeat('a', 321)],
    ], $rules);

    $limite = Validator::make([
        'title' => str_repeat('a', 255),
        'content' => 'C',
        'published_at' => '2026-10-22T14:30',
        'seo' => ['meta_title' => str_repeat('a', 60), 'meta_description' => str_repeat('a', 320)],
    ], $rules);

    expect($limite->passes())->toBeTrue()
        ->and($vide->errors()->keys())->toBe(['title', 'content', 'published_at'])
        ->and($trop->errors()->keys())->toBe(['title', 'seo.meta_title', 'seo.meta_description']);
});
