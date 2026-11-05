<?php

use App\Models\NewsTicker;
use App\Models\Slider;

dataset('resources protegees par le code', [
    'slider' => [Slider::class, 'admin.sliders.destroy', 'admin.sliders.index'],
    'bandeau d actualite' => [NewsTicker::class, 'admin.news-tickers.destroy', 'admin.news-tickers.index'],
]);

it('refuse la suppression sans code a Publication', function (string $model, string $destroy) {
    setDeleteCode('4242');
    $record = $model::factory()->create();

    $this->actingAs(publicationUser())->delete(route($destroy, $record))
        ->assertRedirect()
        ->assertSessionHasErrors('delete_code');

    expect($model::find($record->id))->not->toBeNull();
})->with('resources protegees par le code');

it('refuse la suppression avec un mauvais code a Publication', function (string $model, string $destroy) {
    setDeleteCode('4242');
    $record = $model::factory()->create();

    $this->actingAs(publicationUser())->delete(route($destroy, $record), ['delete_code' => '0000'])
        ->assertRedirect()
        ->assertSessionHasErrors('delete_code');

    expect($model::find($record->id))->not->toBeNull();
})->with('resources protegees par le code');

it('supprime avec le bon code pour Publication', function (string $model, string $destroy) {
    setDeleteCode('4242');
    $record = $model::factory()->create();

    $this->actingAs(publicationUser())->delete(route($destroy, $record), ['delete_code' => '4242'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($model::find($record->id))->toBeNull();
})->with('resources protegees par le code');

it('exempte le Super Admin du code', function (string $model, string $destroy) {
    setDeleteCode('4242');
    $record = $model::factory()->create();

    $this->actingAs(adminUser())->delete(route($destroy, $record))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($model::find($record->id))->toBeNull();
})->with('resources protegees par le code');

it('ajoute le champ code a la liste pour Publication seulement', function (string $model, string $destroy, string $index) {
    $model::factory()->create();

    $this->actingAs(publicationUser())->get(route($index))
        ->assertOk()
        ->assertSee('name="delete_code"', false);

    $this->actingAs(adminUser())->get(route($index))
        ->assertOk()
        ->assertDontSee('name="delete_code"', false);
})->with('resources protegees par le code');
