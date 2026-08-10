<?php

use App\Models\Category;

it('retourne toujours la meme couleur pour la meme categorie', function () {
    $category = Category::create(['name' => 'Sci-tech']);

    $first = $category->badgeColor();
    $second = $category->badgeColor();

    expect($first)->toBe($second);
});

it('retourne une couleur parmi la palette definie', function () {
    $category = Category::create(['name' => 'Tech']);

    expect($category->badgeColor())->toBeIn([
        'bg-blue-100 text-blue-800',
        'bg-yellow-100 text-yellow-800',
        'bg-green-100 text-green-800',
        'bg-purple-100 text-purple-800',
        'bg-pink-100 text-pink-800',
        'bg-orange-100 text-orange-800',
        'bg-teal-100 text-teal-800',
        'bg-red-100 text-red-800',
    ]);
});

it('peut attribuer des couleurs differentes a des categories differentes', function () {
    $categoryA = Category::create(['name' => 'Sci-tech']);
    $categoryB = Category::create(['name' => 'Environnement']);

    // Pas garanti a 100% (hash), mais verifie que la fonction ne renvoie pas
    // toujours la meme valeur peu importe l'entree.
    $colors = collect([$categoryA, $categoryB, Category::create(['name' => 'Culture']), Category::create(['name' => 'Sante'])])
        ->map(fn ($c) => $c->badgeColor())
        ->unique();

    expect($colors->count())->toBeGreaterThan(1);
});