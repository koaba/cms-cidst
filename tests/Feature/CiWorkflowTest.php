<?php

function workflowContent(): ?string
{
    $path = base_path('.github/workflows/tests.yml');

    return is_file($path) ? file_get_contents($path) : null;
}

it('fournit un workflow de tests GitHub Actions', function () {
    expect(workflowContent())->not->toBeNull();
});

it('teste PHP 8.3, 8.4 et 8.5', function () {
    $content = workflowContent() ?? '';

    expect($content)->toContain('8.3')
        ->and($content)->toContain('8.4')
        ->and($content)->toContain('8.5');
});

it('lance Pint puis Pest comme le hook pre-push', function () {
    $content = workflowContent() ?? '';

    expect($content)->toContain('vendor/bin/pint --test')
        ->and($content)->toContain('vendor/bin/pest');
});

it('ne contient aucune etape de mise en ligne', function () {
    $content = strtolower(workflowContent() ?? '');

    expect($content)->not->toContain('ftp')
        ->and($content)->not->toContain('ssh')
        ->and($content)->not->toContain('deploy');
});
