<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Authentifie un admin habilité (rôle "Super Admin") pour les tests Feature.
 *
 * RefreshDatabase vide la base entre chaque test, y compris la table des
 * rôles Spatie — le rôle "Super Admin" n'existe donc pas tant qu'on ne le
 * crée pas explicitement ici (firstOrCreate évite de le recréer en double
 * si plusieurs tests l'appellent dans le même run).
 */
function adminUser(): User
{
    Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    return $user;
}
