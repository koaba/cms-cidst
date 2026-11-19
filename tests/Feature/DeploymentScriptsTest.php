<?php

it('ignore le fichier de configuration du deploiement', function () {
    expect(file_get_contents(base_path('.gitignore')))->toContain('scripts/deployer.config.json');
});

it('garde les scripts de deploiement en ASCII', function (string $file) {
    $content = file_get_contents(base_path($file));

    expect($content)->toBeString();
    expect(mb_check_encoding($content, 'ASCII'))->toBeTrue();
})->with(['scripts/deployer.ps1', 'scripts/deployer.bat', 'scripts/install-serveur.sh']);

it('garde install-serveur.sh en fins de ligne LF', function () {
    expect(file_get_contents(base_path('scripts/install-serveur.sh')))->not->toContain("\r");
});

it('lance deployer.ps1 depuis le fichier .bat', function () {
    expect(file_get_contents(base_path('scripts/deployer.bat')))->toContain('deployer.ps1');
});
