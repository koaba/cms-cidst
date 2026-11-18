<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

function accessAdminRoutes(): Collection
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn (LaravelRoute $route) => str_starts_with($route->uri(), 'admin'))
        ->values();
}

/**
 * @return list<string>
 */
function accessMiddleware(LaravelRoute $route): array
{
    return array_values(array_filter($route->gatherMiddleware(), 'is_string'));
}

function accessUrl(LaravelRoute $route): string
{
    return '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri());
}

function accessLabel(LaravelRoute $route): string
{
    return $route->methods()[0].' /'.$route->uri();
}

it('protege toute route admin par auth et par un role', function () {
    $routes = accessAdminRoutes();

    expect($routes->count())->toBeGreaterThan(50);

    $sansProtection = $routes
        ->filter(function (LaravelRoute $route) {
            $middleware = accessMiddleware($route);

            return ! in_array('auth', $middleware, true)
                || collect($middleware)->doesntContain(fn (string $nom) => str_starts_with($nom, 'role:'));
        })
        ->map(fn (LaravelRoute $route) => accessLabel($route))
        ->values()
        ->all();

    expect($sansProtection)->toBe([]);
});

it('renvoie un visiteur non connecte vers la connexion sur toute route admin', function () {
    $echecs = [];

    foreach (accessAdminRoutes() as $route) {
        $response = $this->call($route->methods()[0], accessUrl($route));

        if (! $response->isRedirect(route('login'))) {
            $echecs[] = accessLabel($route).' -> '.$response->getStatusCode();
        }
    }

    expect($echecs)->toBe([]);
});

it('refuse en 403 au role Publication toute route reservee au Super Admin', function () {
    $reservees = accessAdminRoutes()
        ->filter(fn (LaravelRoute $route) => in_array(UserRole::middleware(UserRole::SuperAdmin), accessMiddleware($route), true))
        ->values();

    expect($reservees->count())->toBeGreaterThan(40)
        ->and($reservees->map(fn (LaravelRoute $route) => $route->getName())->all())
        ->toContain('admin.users.index', 'admin.audience', 'admin.settings.edit', 'admin.messages.index', 'admin.menus.index');

    // Le liage des modeles passe avant le controle du role : on le neutralise pour atteindre le 403.
    $reservees
        ->flatMap(fn (LaravelRoute $route) => $route->parameterNames())
        ->unique()
        ->each(fn (string $nom) => Route::bind($nom, fn () => new User));

    $this->actingAs(publicationUser());
    $echecs = [];

    foreach ($reservees as $route) {
        $statut = $this->call($route->methods()[0], accessUrl($route))->getStatusCode();

        if ($statut !== 403) {
            $echecs[] = accessLabel($route).' -> '.$statut;
        }
    }

    expect($echecs)->toBe([]);
});
