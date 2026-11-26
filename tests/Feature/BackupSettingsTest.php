<?php

use App\Enums\BackupScope;
use App\Enums\BackupStatus;
use App\Models\Backup;
use App\Services\Backup\BackupSettings;
use Illuminate\Support\Carbon;

/** Fonctions globales : prefixe bs. */
function bsAt(string $date): Carbon
{
    return Carbon::parse($date, config('app.timezone'));
}

function bsSettings(array $values): BackupSettings
{
    $settings = app(BackupSettings::class);
    $settings->update($values);

    return $settings;
}

afterEach(fn () => Carbon::setTestNow());

it('renvoie les valeurs par defaut quand rien n est enregistre', function () {
    config(['backup.keep' => 5]);

    expect(app(BackupSettings::class)->all())->toBe([
        'auto' => false, 'frequency' => 'daily', 'hour' => 2, 'weekday' => 7, 'scope' => 'all', 'keep' => 5,
    ]);
});

it('remplace toute valeur invalide par la valeur par defaut', function () {
    config(['backup.keep' => 7]);

    $clean = app(BackupSettings::class)->normalize([
        'auto' => '1', 'frequency' => 'hourly', 'hour' => 99, 'weekday' => 0, 'scope' => 'rm -rf', 'keep' => 500,
    ]);

    expect($clean)->toMatchArray(['auto' => true, 'frequency' => 'daily', 'hour' => 2, 'weekday' => 7, 'scope' => 'all'])
        ->and($clean['keep'])->toBe(7);
});

it('enregistre les options depuis l administration, Super Admin seulement', function () {
    $data = ['auto' => 1, 'frequency' => 'weekly', 'weekday' => 3, 'hour' => 4, 'scope' => 'database', 'keep' => 10];

    $this->actingAs(publicationUser())->put(route('admin.backups.settings'), $data)->assertForbidden();
    $this->actingAs(adminUser())->put(route('admin.backups.settings'), $data)->assertSessionHasNoErrors();

    expect(app(BackupSettings::class)->all())->toBe([
        'auto' => true, 'frequency' => 'weekly', 'hour' => 4, 'weekday' => 3, 'scope' => 'database', 'keep' => 10,
    ]);
});

it('refuse des options invalides sans rien enregistrer', function () {
    $data = ['auto' => 1, 'frequency' => 'daily', 'weekday' => 1, 'hour' => 25, 'scope' => 'all', 'keep' => 7];

    $this->actingAs(adminUser())->put(route('admin.backups.settings'), $data)->assertSessionHasErrors('hour');
    $this->actingAs(adminUser())->put(route('admin.backups.settings'), [...$data, 'hour' => 2, 'scope' => 'tout'])->assertSessionHasErrors('scope');

    expect(app(BackupSettings::class)->all()['auto'])->toBeFalse();
});

it('calcule la derniere echeance quotidienne', function () {
    bsSettings(['auto' => 1, 'frequency' => 'daily', 'hour' => 2]);

    expect(app(BackupSettings::class)->lastDueAt(bsAt('2026-11-26 10:00'))->format('Y-m-d H:i'))->toBe('2026-11-26 02:00')
        ->and(app(BackupSettings::class)->lastDueAt(bsAt('2026-11-26 01:00'))->format('Y-m-d H:i'))->toBe('2026-11-25 02:00');
});

it('calcule la derniere echeance hebdomadaire (dimanche)', function () {
    bsSettings(['auto' => 1, 'frequency' => 'weekly', 'weekday' => 7, 'hour' => 2]);

    // 2026-11-26 est un jeudi ; 2026-11-29 est un dimanche.
    expect(app(BackupSettings::class)->lastDueAt(bsAt('2026-11-26 10:00'))->format('Y-m-d H:i'))->toBe('2026-11-22 02:00')
        ->and(app(BackupSettings::class)->lastDueAt(bsAt('2026-11-29 01:00'))->format('Y-m-d H:i'))->toBe('2026-11-22 02:00')
        ->and(app(BackupSettings::class)->lastDueAt(bsAt('2026-11-29 03:00'))->format('Y-m-d H:i'))->toBe('2026-11-29 02:00');
});

it('annonce la prochaine sauvegarde seulement si elle est activee', function () {
    expect(app(BackupSettings::class)->nextDueAt(bsAt('2026-11-26 10:00')))->toBeNull();

    bsSettings(['auto' => 1, 'frequency' => 'daily', 'hour' => 2]);

    expect(app(BackupSettings::class)->nextDueAt(bsAt('2026-11-26 10:00'))->format('Y-m-d H:i'))->toBe('2026-11-27 02:00');
});

it('backup:auto ne demande rien quand l option est desactivee', function () {
    Carbon::setTestNow(bsAt('2026-11-26 10:00'));

    $this->artisan('backup:auto')->assertSuccessful();

    expect(Backup::count())->toBe(0);
});

it('backup:auto demande une sauvegarde automatique du type choisi, une seule fois par echeance', function () {
    bsSettings(['auto' => 1, 'frequency' => 'daily', 'hour' => 2, 'scope' => 'files']);
    Carbon::setTestNow(bsAt('2026-11-26 10:00'));

    $this->artisan('backup:auto')->assertSuccessful();
    $this->artisan('backup:auto')->assertSuccessful();

    $backup = Backup::sole();
    expect($backup->automatic)->toBeTrue()
        ->and($backup->scope)->toBe(BackupScope::Files)
        ->and($backup->status)->toBe(BackupStatus::Pending)
        ->and($backup->requested_by)->toBeNull();
});

it('backup:auto attend la prochaine echeance apres une sauvegarde automatique', function () {
    bsSettings(['auto' => 1, 'frequency' => 'daily', 'hour' => 2]);
    Backup::forceCreate(['status' => BackupStatus::Done, 'automatic' => true, 'created_at' => bsAt('2026-11-25 02:10'), 'updated_at' => bsAt('2026-11-25 02:10')]);

    Carbon::setTestNow(bsAt('2026-11-26 01:00'));
    $this->artisan('backup:auto')->assertSuccessful();
    expect(Backup::count())->toBe(1);

    Carbon::setTestNow(bsAt('2026-11-26 02:05'));
    $this->artisan('backup:auto')->assertSuccessful();
    expect(Backup::count())->toBe(2);
});

it('backup:auto ne se declenche pas pendant une sauvegarde active et reessaie ensuite', function () {
    bsSettings(['auto' => 1, 'frequency' => 'daily', 'hour' => 2]);
    Carbon::setTestNow(bsAt('2026-11-26 10:00'));
    $running = Backup::create(['status' => BackupStatus::Running]);

    $this->artisan('backup:auto')->assertSuccessful();
    expect(Backup::count())->toBe(1);

    $running->update(['status' => BackupStatus::Done]);
    $this->artisan('backup:auto')->assertSuccessful();
    expect(Backup::count())->toBe(2);
});

it('une sauvegarde manuelle ne retarde pas la sauvegarde automatique', function () {
    bsSettings(['auto' => 1, 'frequency' => 'daily', 'hour' => 2]);
    Carbon::setTestNow(bsAt('2026-11-26 10:00'));
    Backup::create(['status' => BackupStatus::Done, 'automatic' => false]);

    $this->artisan('backup:auto')->assertSuccessful();

    expect(Backup::where('automatic', true)->count())->toBe(1);
});

it('planifie backup:auto a la cinquieme minute de chaque heure', function () {
    expect(scheduledCron('backup:auto'))->toBe('5 * * * *');
});

it('affiche les boutons par type et le formulaire automatique', function () {
    $this->actingAs(adminUser())->get(route('admin.backups.index'))
        ->assertOk()
        ->assertSee('Tout sauvegarder')
        ->assertSee('Base seule')
        ->assertSee('Fichiers seuls')
        ->assertSee('Activer la sauvegarde automatique');
});

it('demande une sauvegarde du type choisi et refuse un type inconnu', function () {
    $admin = adminUser();

    $this->actingAs($admin)->post(route('admin.backups.store'), ['scope' => 'database'])->assertSessionHas('success');
    expect(Backup::sole()->scope)->toBe(BackupScope::Database);

    Backup::query()->delete();
    $this->actingAs($admin)->post(route('admin.backups.store'), ['scope' => 'tout-effacer'])->assertSessionHasErrors('scope');
    expect(Backup::count())->toBe(0);
});

it('le tableau de bord alerte le Super Admin quand aucune sauvegarde n existe', function () {
    $this->actingAs(adminUser())->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Aucune sauvegarde')
        ->assertSee('Sauvegarder maintenant');
});

it('le tableau de bord signale une sauvegarde ancienne puis recente', function () {
    $backup = Backup::forceCreate(['status' => BackupStatus::Done, 'finished_at' => now()->subDays(9), 'created_at' => now()->subDays(9), 'updated_at' => now()->subDays(9)]);

    $this->actingAs(adminUser())->get(route('admin.dashboard'))->assertSee('il y a 9 jours');

    $backup->forceFill(['finished_at' => now()->subHour()])->save();

    $this->actingAs(adminUser())->get(route('admin.dashboard'))
        ->assertDontSee('Aucune sauvegarde')
        ->assertDontSee('pensez');
});

it('le tableau de bord ne montre pas la sauvegarde au role Publication', function () {
    $this->actingAs(publicationUser())->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Sauvegarder maintenant');
});
