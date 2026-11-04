<?php

use App\Enums\UserRole;

it('expose les noms de role stockes en base', function () {
    expect(UserRole::SuperAdmin->value)->toBe('Super Admin')
        ->and(UserRole::Publication->value)->toBe('Publication');
});

it('construit la valeur du middleware Spatie a partir de roles', function () {
    expect(UserRole::middleware(UserRole::SuperAdmin))->toBe('role:Super Admin')
        ->and(UserRole::middleware(UserRole::SuperAdmin, UserRole::Publication))->toBe('role:Super Admin|Publication');
});

it('liste les valeurs de tous les roles', function () {
    expect(UserRole::values())->toBe(['Super Admin', 'Publication']);
});
