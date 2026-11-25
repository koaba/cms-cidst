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

it('vide le cache apres les migrations', function () {
    $script = file_get_contents(base_path('scripts/install-serveur.sh'));
    $clear = strpos($script, 'artisan cache:clear');

    expect($clear)->toBeInt()
        ->and($clear)->toBeGreaterThan(strpos($script, 'artisan migrate --force'))
        ->and($clear)->toBeLessThan(strpos($script, 'artisan config:cache'));
});

it('ouvre dans le pare-feu le port SSH reel et non un profil fixe', function () {
    $script = file_get_contents(base_path('scripts/install-serveur.sh'));

    expect($script)->toContain('sshd -T')
        ->and($script)->toContain('ufw allow "${SSH_PORT:-22}/tcp"')
        ->and($script)->not->toContain('ufw allow OpenSSH');
});

it('met en cache longue duree les fichiers Vite dans la configuration Nginx', function () {
    $script = file_get_contents(base_path('scripts/install-serveur.sh'));

    expect($script)->toContain('location /build/')
        ->and($script)->toContain('immutable');
});

it('sauvegarde la base et storage avant de toucher au code d une installation existante', function () {
    $script = file_get_contents(base_path('scripts/install-serveur.sh'));
    $dump = strpos($script, 'mysqldump --single-transaction');
    $tar = strpos($script, 'storage-app.tgz');

    expect($dump)->toBeInt()
        ->and($tar)->toBeInt()
        ->and($dump)->toBeLessThan(strpos($script, 'git -C "$APP_DIR" checkout --force'))
        ->and($tar)->toBeLessThan(strpos($script, 'git -C "$APP_DIR" checkout --force'))
        ->and($dump)->toBeLessThan(strpos($script, 'artisan migrate --force'));
});

it('ne sauvegarde que sur une installation existante et protege les droits', function () {
    $script = file_get_contents(base_path('scripts/install-serveur.sh'));

    expect($script)->toContain('if [ -d "$APP_DIR/.git" ] && [ -f "$APP_DIR/.env" ]; then')
        ->and($script)->toContain('chmod 700 "$BACKUP_ROOT" "$BACKUP_DIR"')
        ->and($script)->toContain('chmod 600 "$BACKUP_DIR"/*');
});

it('limite le nombre de sauvegardes conservees', function () {
    $script = file_get_contents(base_path('scripts/install-serveur.sh'));

    expect($script)->toMatch('/^BACKUP_KEEP=7$/m')
        ->and($script)->toContain('tail -n +"$((BACKUP_KEEP + 1))"');
});

it('lance deployer.ps1 depuis le fichier .bat', function () {
    expect(file_get_contents(base_path('scripts/deployer.bat')))->toContain('deployer.ps1');
});
