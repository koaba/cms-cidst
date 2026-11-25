<?php

use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Services\Backup\BackupService;
use Illuminate\Support\Facades\File;

/**
 * Isole une sauvegarde dans un dossier temporaire : base SQLite fichier + faux storage/app.
 * (Fonctions globales : prefixe bk.)
 */
function bkSandbox(): string
{
    $root = sys_get_temp_dir().'/bk-'.uniqid();
    File::ensureDirectoryExists($root.'/src/app');
    file_put_contents($root.'/src/app/image.txt', 'contenu de test');
    file_put_contents($root.'/db.sqlite', 'faux sqlite');

    config([
        'backup.path' => $root.'/out',
        'backup.storage_path' => $root.'/src/app',
        'backup.connection' => 'bk',
        'database.connections.bk' => ['driver' => 'sqlite', 'database' => $root.'/db.sqlite'],
    ]);

    return $root;
}

function bkDone(string $dir, int $id): Backup
{
    File::ensureDirectoryExists(Backup::root().'/'.$dir);
    file_put_contents(Backup::root().'/'.$dir.'/base.sql.gz', 'a');
    file_put_contents(Backup::root().'/'.$dir.'/storage-app.tgz', 'b');

    return Backup::forceCreate(['id' => $id, 'status' => BackupStatus::Done, 'directory' => $dir]);
}

beforeEach(fn () => $this->bkRoot = bkSandbox());
afterEach(fn () => File::deleteDirectory($this->bkRoot));

it('reserve les sauvegardes au Super Admin', function () {
    $this->get(route('admin.backups.index'))->assertRedirect(route('login'));
    $this->actingAs(publicationUser())->get(route('admin.backups.index'))->assertForbidden();
    $this->actingAs(publicationUser())->post(route('admin.backups.store'))->assertForbidden();
    $this->actingAs(adminUser())->get(route('admin.backups.index'))->assertOk()->assertSee('Sauvegarder maintenant');
});

it('enregistre une demande puis refuse un doublon', function () {
    $admin = adminUser();

    $this->actingAs($admin)->post(route('admin.backups.store'))->assertSessionHas('success');
    $this->actingAs($admin)->post(route('admin.backups.store'))->assertSessionHas('error');

    expect(Backup::count())->toBe(1)
        ->and(Backup::first()->status)->toBe(BackupStatus::Pending)
        ->and(Backup::first()->requested_by)->toBe($admin->id);
});

it('traite la demande : base et fichiers archives, droits restreints', function () {
    $backup = Backup::create(['status' => BackupStatus::Pending]);

    $this->artisan('backup:process')->assertSuccessful();

    $backup->refresh();
    $base = $backup->filePath(BackupService::DATABASE_FILE);
    $files = $backup->filePath(BackupService::STORAGE_FILE);

    expect($backup->status)->toBe(BackupStatus::Done)
        ->and($backup->directory)->toMatch(Backup::DIRECTORY_PATTERN)
        ->and(gzdecode(file_get_contents($base)))->toBe('faux sqlite')
        ->and($backup->db_size)->toBe(filesize($base))
        ->and($backup->files_size)->toBe(filesize($files));

    $listing = gzdecode((string) file_get_contents($files));
    expect($listing)->toContain('app/image.txt');
});

it('restreint les droits des fichiers de sauvegarde (600)', function () {
    $backup = Backup::create(['status' => BackupStatus::Pending]);

    $this->artisan('backup:process')->assertSuccessful();

    $backup->refresh();

    expect(substr(sprintf('%o', fileperms($backup->filePath(BackupService::DATABASE_FILE))), -3))->toBe('600');
})->skip(PHP_OS_FAMILY === 'Windows', 'Droits Unix non applicables sous Windows.');

it('ne fait rien sans demande en attente', function () {
    $this->artisan('backup:process')->assertSuccessful();

    expect(Backup::count())->toBe(0)->and(is_dir(Backup::root()))->toBeFalse();
});

it('marque la sauvegarde en echec et ne laisse aucun dossier', function () {
    config(['database.connections.bk' => ['driver' => 'pgsql']]);
    $backup = Backup::create(['status' => BackupStatus::Pending]);

    $this->artisan('backup:process')->assertFailed();

    $backup->refresh();
    expect($backup->status)->toBe(BackupStatus::Failed)
        ->and($backup->error)->toContain('pgsql')
        ->and(glob(Backup::root().'/*'))->toBe([]);
});

it('declare en echec une sauvegarde bloquee depuis trop longtemps', function () {
    $stale = Backup::create(['status' => BackupStatus::Running, 'started_at' => now()->subHours(3)]);
    $recent = Backup::create(['status' => BackupStatus::Running, 'started_at' => now()->subMinutes(5)]);

    $this->artisan('backup:process')->assertSuccessful();

    expect($stale->refresh()->status)->toBe(BackupStatus::Failed)
        ->and($recent->refresh()->status)->toBe(BackupStatus::Running);
});

it('ne conserve que les N dernieres sauvegardes terminees', function () {
    config(['backup.keep' => 2]);
    bkDone('20260101-000001', 1);
    bkDone('20260101-000002', 2);
    bkDone('20260101-000003', 3);

    app(BackupService::class)->prune();

    expect(Backup::pluck('id')->all())->toBe([2, 3])
        ->and(is_dir(Backup::root().'/20260101-000001'))->toBeFalse()
        ->and(is_dir(Backup::root().'/20260101-000003'))->toBeTrue();
});

it('telecharge uniquement les fichiers de la liste blanche', function () {
    $backup = bkDone('20260101-000001', 1);
    $admin = adminUser();

    $this->actingAs($admin)->get(route('admin.backups.download', [$backup, 'database']))->assertOk()->assertDownload('cidst-20260101-000001-base.sql.gz');
    $this->actingAs($admin)->get(route('admin.backups.download', [$backup, 'files']))->assertOk();
    $this->actingAs($admin)->get('/admin/backups/1/download/..%2F..%2F.env')->assertNotFound();
    $this->actingAs($admin)->get('/admin/backups/1/download/secret')->assertNotFound();

    $backup->update(['status' => BackupStatus::Failed]);
    $this->actingAs($admin)->get(route('admin.backups.download', [$backup, 'database']))->assertNotFound();
});

it('supprime une sauvegarde et son dossier, sauf si elle est active', function () {
    $backup = bkDone('20260101-000001', 1);
    $active = Backup::create(['status' => BackupStatus::Running]);
    $admin = adminUser();

    $this->actingAs($admin)->delete(route('admin.backups.destroy', $active))->assertStatus(409);
    $this->actingAs($admin)->delete(route('admin.backups.destroy', $backup))->assertSessionHas('success');

    expect(Backup::find(1))->toBeNull()->and(is_dir(Backup::root().'/20260101-000001'))->toBeFalse();
});

it('ne supprime jamais un dossier hors du format genere', function () {
    File::ensureDirectoryExists(Backup::root().'/precieux');
    $backup = Backup::forceCreate(['status' => BackupStatus::Done, 'directory' => 'precieux']);

    $backup->delete();

    expect(is_dir(Backup::root().'/precieux'))->toBeTrue();
});

it('planifie le traitement chaque minute sans chevauchement', function () {
    expect(scheduledCron('backup:process'))->toBe('* * * * *');
});
