<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;

afterEach(fn () => Carbon::setTestNow());

function fdAt(string $date): void
{
    Carbon::setTestNow(Carbon::parse($date, config('app.timezone')));
}

const FD_DATES = [
    'noel' => ['2026-12-25', 'noel'],
    'nouvelan' => ['2026-12-31', 'nouvelan'],
    'paques' => ['2026-04-05', 'paques'],
    'pentecote' => ['2026-05-24', 'pentecote'],
    'independance' => ['2026-06-26', 'independance'],
];

it('habille le site public pendant la fete (bandeau et logo)', function (string $date, string $decor) {
    fdAt($date);

    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, 'data-festival-decor="'.$decor.'"'))->toBe(2)
        ->and($html)->toContain('data-variant="strip"')
        ->and($html)->toContain('data-variant="logo"');
})->with(FD_DATES);

it('n affiche aucun decor hors fenetre', function (string $date) {
    fdAt($date);

    $this->get('/')->assertOk()->assertDontSee('data-festival-decor', false);
})->with(['hors fete' => '2026-07-15', 'lendemain de la fenetre' => '2027-01-03']);

it('n affiche aucun decor dans l administration', function () {
    fdAt('2026-12-25');

    $this->actingAs(adminUser())->get('/admin')->assertOk()->assertDontSee('data-festival-decor', false);
});

it('respecte l interrupteur general', function () {
    fdAt('2026-12-25');
    config(['festivals.enabled' => false]);

    $this->get('/')->assertOk()->assertDontSee('data-festival-decor', false);
});

it('produit un decor inerte : aria-hidden, sans souris, sans ressource externe', function (string $date, string $decor) {
    fdAt($date);

    foreach (['strip', 'logo'] as $variant) {
        $html = Blade::render('<x-festival-decor variant="'.$variant.'" />');

        expect($html)->toContain('aria-hidden="true"')
            ->and($html)->toContain('pointer-events-none')
            ->and($html)->toContain('<svg')
            ->and($html)->not->toMatch('/https?:|href=|src=|url\(|<script|<img/i');
    }
})->with(FD_DATES);

it('garde le bandeau court sur mobile (4 icones visibles, le reste a partir de sm)', function () {
    fdAt('2026-12-25');

    $html = Blade::render('<x-festival-decor />');

    expect(substr_count($html, '<svg'))->toBe(7)
        ->and(substr_count($html, 'hidden sm:block'))->toBe(3);
});

it('declare un jeu d icones complet pour chaque fete', function () {
    foreach (config('festivals.festivals') as $key => $festival) {
        $set = config('festivals.decors.'.$festival['decor']);

        expect($set)->toBeArray("decor manquant pour $key")
            ->and($set['strip'])->not->toBeEmpty();

        foreach ([$set['logo'], ...$set['strip']] as $icon) {
            expect(view()->exists('partials.festival.icons.'.$icon))->toBeTrue("icone $icon absente");
        }
    }
});

it('coupe les animations pour les visiteurs qui les reduisent', function () {
    $css = (string) file_get_contents(resource_path('css/shared.css'));

    expect($css)->toContain('.festival-float')
        ->and($css)->toMatch('/prefers-reduced-motion:\s*reduce\)\s*\{\s*\.festival-float\s*\{\s*animation:\s*none/');
});

it('place le decor dans le layout public et jamais dans celui de l administration', function () {
    expect(file_get_contents(resource_path('views/components/layout.blade.php')))->toContain('<x-festival-decor />')
        ->and(file_get_contents(resource_path('views/components/main-menu.blade.php')))->toContain('<x-festival-decor variant="logo" />')
        ->and(file_get_contents(resource_path('views/components/admin/layout.blade.php')))->not->toContain('festival-decor');
});
