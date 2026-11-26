<?php

use App\Console\Commands\RestoreBackupFiles;
use App\Enums\BackupScope;
use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Services\Backup\BackupSettings;
use App\Services\Backup\FileMirror;
use Illuminate\Support\Facades\File;

/**
 * Copie miroir de storage/app : bac a sable dans le dossier temporaire (faux storage/app + faux miroir).
 * Fonctions globales : prefixe bm.
 */
function bmSandbox(): string
{
    $root = sys_get_temp_dir().'/bm-'.uniqid();
    File::ensureDirectoryExists($root.'/src/app/sous');
    file_put_contents($root.'/src/app/a.txt', 'aaaa');
    file_put_contents($root.'/src/app/sous/b.txt', 'bbbb');

    config([
        'backup.path' => $root.'/out',
        'backup.storage_path' => $root.'/src/app',
        'backup.mirror.path' => $root.'/miroir',
        'backup.mirror.reserve' => 0,
    ]);

    return $root;
}

beforeEach(fn () => $this->bmRoot = bmSandbox());
afterEach(fn () => File::deleteDirectory($this->bmRoot));

it('copie les fichiers, sous-dossiers compris, et ecrit l etat', function () {
    $result = app(FileMirror::class)->sync();
    $state = app(FileMirror::class)->state();

    expect($result['copied'])->toBe(2)
        ->and(file_get_contents($this->bmRoot.'/miroir/app/sous/b.txt'))->toBe('bbbb')
        ->and($state['files'])->toBe(2)
        ->and($state['synced_at'])->not->toBeNull()
        ->and($state['error'])->toBeNull();
});

it('ne recopie que ce qui a change', function () {
    $mirror = app(FileMirror::class);
    $mirror->sync();

    expect($mirror->sync()['copied'])->toBe(0);

    file_put_contents($this->bmRoot.'/src/app/nouveau.txt', 'neuf');

    expect($mirror->sync()['copied'])->toBe(1);
});

it('recopie un fichier plus recent meme si sa taille est identique', function () {
    $mirror = app(FileMirror::class);
    $mirror->sync();

    $source = $this->bmRoot.'/src/app/a.txt';
    $copie = $this->bmRoot.'/miroir/app/a.txt';
    file_put_contents($source, 'cccc');
    touch($source, time() + 100);
    touch($copie, time() - 100);

    expect($mirror->sync()['copied'])->toBe(1)
        ->and(file_get_contents($copie))->toBe('cccc');
});

it('ne supprime jamais du miroir un fichier supprime du site', function () {
    $mirror = app(FileMirror::class);
    $mirror->sync();

    unlink($this->bmRoot.'/src/app/sous/b.txt');
    $mirror->sync();

    expect(is_file($this->bmRoot.'/miroir/app/sous/b.txt'))->toBeTrue();
});

it('refuse de copier quand l espace disque manque, sans rien ecrire', function () {
    config(['backup.mirror.reserve' => PHP_INT_MAX >> 1]);

    expect(fn () => app(FileMirror::class)->sync())->toThrow(RuntimeException::class, 'Espace disque insuffisant')
        ->and(is_file($this->bmRoot.'/miroir/app/a.txt'))->toBeFalse();
});

it('refuse un miroir place dans storage/app', function () {
    config(['backup.mirror.path' => $this->bmRoot.'/src/app/miroir']);

    expect(fn () => app(FileMirror::class)->sync())->toThrow(RuntimeException::class, 'hors de storage/app');
});

it('restaure ce qui manque sans jamais ecraser un fichier existant', function () {
    $mirror = app(FileMirror::class);
    $mirror->sync();

    file_put_contents($this->bmRoot.'/miroir/app/a.txt', 'MIROIR');
    file_put_contents($this->bmRoot.'/src/app/a.txt', 'ACTUEL');
    unlink($this->bmRoot.'/src/app/sous/b.txt');

    $result = $mirror->restore();

    expect($result['copied'])->toBe(1)
        ->and(file_get_contents($this->bmRoot.'/src/app/a.txt'))->toBe('ACTUEL')
        ->and(file_get_contents($this->bmRoot.'/src/app/sous/b.txt'))->toBe('bbbb')
        ->and($mirror->state()['restored_at'])->not->toBeNull();
});

it('refuse de restaurer sans copie miroir', function () {
    expect(fn () => app(FileMirror::class)->restore())->toThrow(RuntimeException::class, 'Aucune copie miroir');
});

it('n accepte qu une action connue', function () {
    $mirror = app(FileMirror::class);

    expect(fn () => $mirror->request('rm -rf'))->toThrow(InvalidArgumentException::class);

    $mirror->request(FileMirror::RESTORE);
    expect($mirror->state()['request'])->toBe('restore');
});

it('backup:sync-files ne fait rien quand l option est desactivee', function () {
    $this->artisan('backup:sync-files')->assertSuccessful();

    expect(is_dir($this->bmRoot.'/miroir/app'))->toBeFalse();
});

it('backup:sync-files copie quand l option est activee', function () {
    app(BackupSettings::class)->update(['mirror' => 1]);

    $this->artisan('backup:sync-files')->assertSuccessful();

    expect(file_get_contents($this->bmRoot.'/miroir/app/a.txt'))->toBe('aaaa');
});

it('backup:sync-files traite une demande de restauration puis l efface', function () {
    $mirror = app(FileMirror::class);
    $mirror->sync();
    unlink($this->bmRoot.'/src/app/a.txt');
    $mirror->request(FileMirror::RESTORE);

    $this->artisan('backup:sync-files')->assertSuccessful();

    expect(is_file($this->bmRoot.'/src/app/a.txt'))->toBeTrue()
        ->and($mirror->state()['request'])->toBeNull();
});

it('backup:sync-files enregistre l erreur et efface la demande en cas d echec', function () {
    config(['backup.mirror.reserve' => PHP_INT_MAX >> 1]);
    app(FileMirror::class)->request(FileMirror::SYNC);

    $this->artisan('backup:sync-files')->assertFailed();

    $state = app(FileMirror::class)->state();
    expect($state['error'])->toContain('Espace disque insuffisant')
        ->and($state['request'])->toBeNull();
});

it('backup:restore-files demande confirmation, puis restaure avec --force', function () {
    $mirror = app(FileMirror::class);
    $mirror->sync();
    unlink($this->bmRoot.'/src/app/a.txt');

    $this->artisan('backup:restore-files')->expectsConfirmation(RestoreBackupFiles::QUESTION, 'no')->assertFailed();
    expect(is_file($this->bmRoot.'/src/app/a.txt'))->toBeFalse();

    $this->artisan('backup:restore-files', ['--force' => true])->assertSuccessful();
    expect(is_file($this->bmRoot.'/src/app/a.txt'))->toBeTrue();
});

it('planifie backup:sync-files toutes les 5 minutes', function () {
    expect(scheduledCron('backup:sync-files'))->toBe('*/5 * * * *');
});

it('reserve la copie miroir au Super Admin et enregistre la demande', function () {
    $this->post(route('admin.backups.mirror'), ['action' => 'sync'])->assertRedirect(route('login'));
    $this->actingAs(publicationUser())->post(route('admin.backups.mirror'), ['action' => 'sync'])->assertForbidden();

    $this->actingAs(adminUser())->post(route('admin.backups.mirror'), ['action' => 'restore'])->assertSessionHas('success');

    expect(app(FileMirror::class)->state()['request'])->toBe('restore');
});

it('refuse une action inconnue et une demande en double', function () {
    $admin = adminUser();

    $this->actingAs($admin)->post(route('admin.backups.mirror'), ['action' => 'tout-effacer'])->assertSessionHasErrors('action');
    expect(app(FileMirror::class)->state()['request'])->toBeNull();

    $this->actingAs($admin)->post(route('admin.backups.mirror'), ['action' => 'sync']);
    $this->actingAs($admin)->post(route('admin.backups.mirror'), ['action' => 'restore'])->assertSessionHas('error');

    expect(app(FileMirror::class)->state()['request'])->toBe('sync');
});

it('affiche le bloc miroir et le telechargement de la derniere sauvegarde complete', function () {
    $admin = adminUser();

    $this->actingAs($admin)->get(route('admin.backups.index'))
        ->assertOk()
        ->assertSee('Copie miroir des fichiers')
        ->assertSee('Copier maintenant')
        ->assertSee('Restaurer')
        ->assertSee('Aucune sauvegarde compl&egrave;te termin&eacute;e', false)
        ->assertSee('Tout t&eacute;l&eacute;charger', false);

    Backup::forceCreate(['status' => BackupStatus::Done, 'scope' => BackupScope::All, 'directory' => '20261126-010101']);

    $this->actingAs($admin)->get(route('admin.backups.index'))
        ->assertDontSee('Aucune sauvegarde compl&egrave;te termin&eacute;e', false)
        ->assertSee(route('admin.backups.download', [Backup::sole(), 'files']), false);
});
