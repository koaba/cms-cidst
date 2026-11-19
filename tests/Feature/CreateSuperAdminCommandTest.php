<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\PendingCommand;
use Spatie\Permission\Models\Role;

function csaRun(string $email, string $password, string $confirm): PendingCommand
{
    return test()->artisan('app:create-super-admin', ['email' => $email, '--name' => 'Admin CIDST'])
        ->expectsQuestion('Mot de passe', $password)
        ->expectsQuestion('Confirmez le mot de passe', $confirm);
}

it('cree le premier Super Admin avec un mot de passe a changer', function () {
    csaRun('admin@cidst.mg', 'Secret-Pass-1', 'Secret-Pass-1')->assertSuccessful();

    $user = User::where('email', 'admin@cidst.mg')->firstOrFail();
    expect($user->hasRole(UserRole::SuperAdmin))->toBeTrue();
    expect($user->name)->toBe('Admin CIDST');
    expect($user->must_change_password)->toBeTrue();
    expect(Hash::check('Secret-Pass-1', $user->password))->toBeTrue();
});

it('cree aussi le role Publication, absent d\'une base vierge', function () {
    csaRun('admin@cidst.mg', 'Secret-Pass-1', 'Secret-Pass-1')->assertSuccessful();

    expect(Role::where('name', UserRole::Publication->value)->exists())->toBeTrue();
});

it('refuse quand un Super Admin existe deja', function () {
    adminUser();

    test()->artisan('app:create-super-admin', ['email' => 'autre@cidst.mg'])->assertFailed();

    expect(User::where('email', 'autre@cidst.mg')->exists())->toBeFalse();
});

it('refuse une adresse deja utilisee', function () {
    User::factory()->create(['email' => 'pris@cidst.mg']);

    csaRun('pris@cidst.mg', 'Secret-Pass-1', 'Secret-Pass-1')->assertFailed();

    expect(User::count())->toBe(1);
});

it('refuse une adresse invalide', function () {
    csaRun('pas-une-adresse', 'Secret-Pass-1', 'Secret-Pass-1')->assertFailed();

    expect(User::count())->toBe(0);
});

it('refuse deux mots de passe differents', function () {
    csaRun('admin@cidst.mg', 'Secret-Pass-1', 'Autre-Pass-2')->assertFailed();

    expect(User::count())->toBe(0);
});

it('genere un mot de passe sans le demander', function () {
    test()->artisan('app:create-super-admin', ['email' => 'admin@cidst.mg', '--generate' => true])
        ->expectsOutputToContain('Mot de passe genere')
        ->assertSuccessful();

    $user = User::where('email', 'admin@cidst.mg')->firstOrFail();
    expect($user->hasRole(UserRole::SuperAdmin))->toBeTrue();
});
