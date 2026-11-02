<?php

use App\Http\Controllers\Admin\PdfCategoryController;
use App\Http\Requests\Admin\PdfCategoryRequest;
use App\Models\PdfCategory;
use Illuminate\Support\Facades\Validator;

it('cree une categorie de PDF', function () {
    $this->actingAs(adminUser())
        ->post(route('admin.pdf-categories.store'), ['name' => 'Rapports'])
        ->assertRedirect(route('admin.pdf-categories.index'))
        ->assertSessionHasNoErrors();

    expect(PdfCategory::where('name', 'Rapports')->exists())->toBeTrue();
});

it('refuse une categorie sans nom', function () {
    $this->actingAs(adminUser())
        ->post(route('admin.pdf-categories.store'), ['name' => ''])
        ->assertSessionHasErrors('name');

    expect(PdfCategory::count())->toBe(0);
});

it('renomme une categorie de PDF', function () {
    $category = PdfCategory::factory()->create(['name' => 'Avant']);

    $this->actingAs(adminUser())
        ->put(route('admin.pdf-categories.update', $category), ['name' => 'Apres'])
        ->assertRedirect(route('admin.pdf-categories.index'))
        ->assertSessionHasNoErrors();

    expect($category->fresh()->name)->toBe('Apres');
});

it('refuse un nom trop long a la mise a jour', function () {
    $category = PdfCategory::factory()->create(['name' => 'Avant']);

    $this->actingAs(adminUser())
        ->put(route('admin.pdf-categories.update', $category), ['name' => str_repeat('a', 256)])
        ->assertSessionHasErrors('name');

    expect($category->fresh()->name)->toBe('Avant');
});

it('impose les limites de la FormRequest des categories de PDF', function () {
    $rules = (new PdfCategoryRequest)->rules();

    expect(Validator::make([], $rules)->errors()->keys())->toBe(['name'])
        ->and(Validator::make(['name' => str_repeat('a', 255)], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['name' => str_repeat('a', 256)], $rules)->errors()->keys())->toBe(['name']);
});

it('branche le controleur des categories de PDF sur la FormRequest', function () {
    $type = fn (string $method) => (new ReflectionMethod(PdfCategoryController::class, $method))
        ->getParameters()[0]->getType()->getName();

    expect($type('store'))->toBe(PdfCategoryRequest::class)
        ->and($type('update'))->toBe(PdfCategoryRequest::class);
});
