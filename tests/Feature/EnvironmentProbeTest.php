<?php

use App\Services\Environment\EnvironmentProbe;

function probeWithDisabled(array $disabled): ?array
{
    try {
        $probe = new class($disabled) extends EnvironmentProbe
        {
            public function __construct(private array $fakeDisabled) {}

            protected function disabledFunctions(): array
            {
                return $this->fakeDisabled;
            }
        };

        return $probe->collect();
    } catch (Throwable) {
        return null;
    }
}

it('ne lance pas pdftoppm quand proc_open est desactive', function () {
    $collected = probeWithDisabled(['proc_open']);

    expect($collected)->not->toBeNull()
        ->and($collected['pdftoppm'])->toBeFalse()
        ->and($collected['disabled_functions'])->toBe(['proc_open']);
});

it('renvoie la version reelle de PHP et un booleen pour WebP', function () {
    $collected = (new EnvironmentProbe)->collect();

    expect($collected['php_version'])->toBe(PHP_VERSION)
        ->and($collected['gd_webp'])->toBeBool();
});
