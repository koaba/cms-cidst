<?php

use Spatie\Permission\Models\Role;

it('utilise le modele Role de Spatie', function () {
    expect(config('permission.models.role'))->toBe(Role::class);
});

it('ne garde plus de modele Role local en doublon', function () {
    expect(class_exists('App\\Models\\Role'))->toBeFalse();
});
