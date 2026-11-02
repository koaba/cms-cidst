<?php

use App\Http\Controllers\Admin\NewsTickerController;
use App\Http\Requests\Admin\NewsTickerRequest;
use App\Models\NewsTicker;
use Illuminate\Support\Facades\Validator;

it('cree une actualite avec les valeurs par defaut', function () {
    $this->actingAs(adminUser())
        ->post(route('admin.news-tickers.store'), ['content' => 'Bonjour'])
        ->assertRedirect(route('admin.news-tickers.index'))
        ->assertSessionHasNoErrors();

    $ticker = NewsTicker::firstWhere('content', 'Bonjour');

    expect($ticker->is_active)->toBeFalse()
        ->and($ticker->order)->toBe(0);
});

it('enregistre le lien, l ordre et l etat actif a la creation', function () {
    $this->actingAs(adminUser())
        ->post(route('admin.news-tickers.store'), [
            'content' => 'Info', 'link_url' => '/pages', 'order' => 3, 'is_active' => '1',
        ])
        ->assertSessionHasNoErrors();

    $ticker = NewsTicker::firstWhere('content', 'Info');

    expect($ticker->link_url)->toBe('/pages')
        ->and($ticker->order)->toBe(3)
        ->and($ticker->is_active)->toBeTrue();
});

it('refuse une actualite sans contenu', function () {
    $this->actingAs(adminUser())
        ->post(route('admin.news-tickers.store'), ['link_url' => '/x'])
        ->assertSessionHasErrors('content');

    expect(NewsTicker::count())->toBe(0);
});

it('met a jour une actualite et reapplique les valeurs par defaut', function () {
    $ticker = NewsTicker::factory()->create(['content' => 'Avant', 'order' => 5, 'is_active' => true]);

    $this->actingAs(adminUser())
        ->put(route('admin.news-tickers.update', $ticker), ['content' => 'Apres'])
        ->assertRedirect(route('admin.news-tickers.index'))
        ->assertSessionHasNoErrors();

    $ticker->refresh();

    expect($ticker->content)->toBe('Apres')
        ->and($ticker->order)->toBe(0)
        ->and($ticker->is_active)->toBeFalse();
});

it('refuse a la mise a jour un contenu trop long', function () {
    $ticker = NewsTicker::factory()->create(['content' => 'Avant']);

    $this->actingAs(adminUser())
        ->put(route('admin.news-tickers.update', $ticker), ['content' => str_repeat('a', 256)])
        ->assertSessionHasErrors('content');

    expect($ticker->fresh()->content)->toBe('Avant');
});

it('impose les limites de la FormRequest des actualites', function () {
    $rules = (new NewsTickerRequest)->rules();

    expect(Validator::make([], $rules)->errors()->keys())->toBe(['content'])
        ->and(Validator::make(['content' => str_repeat('a', 255)], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['content' => str_repeat('a', 256)], $rules)->errors()->keys())->toBe(['content'])
        ->and(Validator::make(['content' => 'A', 'link_url' => str_repeat('a', 256)], $rules)->errors()->keys())->toBe(['link_url'])
        ->and(Validator::make(['content' => 'A', 'order' => 'abc'], $rules)->errors()->keys())->toBe(['order']);
});

it('branche le controleur des actualites sur la FormRequest', function () {
    $type = fn (string $method) => (new ReflectionMethod(NewsTickerController::class, $method))
        ->getParameters()[0]->getType()->getName();

    expect($type('store'))->toBe(NewsTickerRequest::class)
        ->and($type('update'))->toBe(NewsTickerRequest::class);
});
