<?php

function assetSource(string $path): string
{
    return file_get_contents(base_path($path));
}

it('le layout public ne charge aucun javascript', function () {
    $layout = assetSource('resources/views/components/layout.blade.php');

    expect($layout)
        ->toContain("@vite('resources/css/public.css')")
        ->not->toContain('.js')
        ->not->toContain('googleapis');
});

it('le layout admin garde app.css et app.js', function () {
    $layout = assetSource('resources/views/components/admin/layout.blade.php');

    expect($layout)->toContain("@vite(['resources/css/app.css', 'resources/js/app.js'])");
});

it('app.js ne contient ni Chart.js ni code de dashboard', function () {
    $js = assetSource('resources/js/app.js');

    expect($js)->toContain('alpinejs')->not->toContain('chart');
});

it('Chart.js n est charge que par le dashboard admin avec des modules explicites', function () {
    $dashboard = assetSource('resources/js/admin/dashboard.js');

    expect($dashboard)
        ->toContain("from 'chart.js'")
        ->not->toContain('chart.js/auto')
        ->toContain('Chart.register(');

    expect(assetSource('resources/views/admin/dashboard.blade.php'))
        ->toContain("@vite('resources/js/admin/dashboard.js')");

    expect(assetSource('vite.config.js'))->toContain('resources/js/admin/dashboard.js');
});

it('la config Tailwind publique n inclut ni DaisyUI ni les vues admin', function () {
    $config = assetSource('tailwind.public.config.js');

    expect($config)
        ->not->toContain('daisyui')
        ->not->toContain('views/admin')
        ->not->toContain('views/auth')
        ->not->toContain('storage/framework');

    expect(assetSource('resources/css/public.css'))->toContain('tailwind.public.config.js');
});

it('les polices sont auto-hebergees, sans Google Fonts', function () {
    foreach (['resources/css/app.css', 'resources/css/public.css', 'resources/css/fonts.css'] as $file) {
        expect(assetSource($file))->not->toContain('googleapis');
    }

    expect(assetSource('resources/css/fonts.css'))
        ->toContain('@fontsource/inter')
        ->toContain('@fontsource/space-grotesk')
        ->toContain('@fontsource/jetbrains-mono');
});

it('les vues publiques n utilisent plus de classes DaisyUI', function () {
    $contact = assetSource('resources/views/public/contact.blade.php');

    expect($contact)->not->toMatch('/class="[^"]*\b(btn|alert|alert-success|btn-primary)\b/');
});
it('public.css place les @import avant @config et @tailwind', function () {
    $css = assetSource('resources/css/public.css');

    $lastImport = strrpos($css, '@import');
    $config = strpos($css, '@config');
    $tailwind = strpos($css, '@tailwind');

    expect($lastImport)->not->toBeFalse()
        ->and($config)->not->toBeFalse()
        ->and($tailwind)->not->toBeFalse()
        ->and($lastImport)->toBeLessThan($config)
        ->and($config)->toBeLessThan($tailwind);

    expect($css)
        ->toContain("@import './fonts.css';")
        ->toContain("@import './shared.css';");
});
