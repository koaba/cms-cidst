<?php

namespace App\Services\Backup;

use App\Enums\BackupScope;
use App\Models\Backup;
use App\Models\SiteSetting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Options de sauvegarde automatique (Administration > Sauvegardes), stockees dans site_settings.backup_options.
 * Meme principe que FestivalSettings : liste blanche a l'ecriture, valeurs par defaut a la lecture
 * (colonne absente ou modele en cache avant migration => defauts).
 */
final class BackupSettings
{
    public const FREQUENCIES = ['daily', 'weekly'];

    /** Jours ISO (1 = lundi). */
    public const WEEKDAYS = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];

    /** @return array{auto: bool, frequency: string, hour: int, weekday: int, scope: string, keep: int} */
    public function all(): array
    {
        $stored = SiteSetting::current()->getAttribute('backup_options');

        return $this->normalize(is_array($stored) ? $stored : []);
    }

    public function keep(): int
    {
        return $this->all()['keep'];
    }

    /**
     * @param  array<array-key, mixed>  $input
     * @return array{auto: bool, frequency: string, hour: int, weekday: int, scope: string, keep: int}
     */
    public function normalize(array $input): array
    {
        $defaults = $this->defaults();
        $frequency = $input['frequency'] ?? null;
        $scope = $input['scope'] ?? null;

        return [
            'auto' => filter_var($input['auto'] ?? $defaults['auto'], FILTER_VALIDATE_BOOLEAN),
            'frequency' => in_array($frequency, self::FREQUENCIES, true) ? (string) $frequency : $defaults['frequency'],
            'hour' => $this->int($input['hour'] ?? null, 0, 23, $defaults['hour']),
            'weekday' => $this->int($input['weekday'] ?? null, 1, 7, $defaults['weekday']),
            'scope' => in_array($scope, array_column(BackupScope::cases(), 'value'), true) ? (string) $scope : $defaults['scope'],
            'keep' => $this->int($input['keep'] ?? null, 1, 30, $defaults['keep']),
        ];
    }

    /** @param  array<array-key, mixed>  $input */
    public function update(array $input): void
    {
        SiteSetting::current()->update(['backup_options' => $this->normalize($input)]);
    }

    /** Derniere echeance (passee ou presente) de la sauvegarde automatique, a l'heure de l'application. */
    public function lastDueAt(CarbonInterface $now): CarbonImmutable
    {
        $options = $this->all();
        $now = CarbonImmutable::instance($now)->setTimezone((string) config('app.timezone'));
        $at = $now->setTime($options['hour'], 0);

        if ($options['frequency'] === 'weekly') {
            $at = $at->subDays(($at->isoWeekday() - $options['weekday'] + 7) % 7);
        }

        return $at->greaterThan($now) ? $at->subDays($this->stepDays($options['frequency'])) : $at;
    }

    public function nextDueAt(CarbonInterface $now): ?CarbonImmutable
    {
        $options = $this->all();

        return $options['auto'] ? $this->lastDueAt($now)->addDays($this->stepDays($options['frequency'])) : null;
    }

    /** Vrai si l'option est active et qu'aucune sauvegarde automatique n'a ete demandee depuis la derniere echeance. */
    public function isDue(CarbonInterface $now): bool
    {
        return $this->all()['auto']
            && ! Backup::query()->where('automatic', true)->where('created_at', '>=', $this->lastDueAt($now))->exists();
    }

    /** @return array{auto: bool, frequency: string, hour: int, weekday: int, scope: string, keep: int} */
    private function defaults(): array
    {
        return [
            'auto' => false,
            'frequency' => 'daily',
            'hour' => 2,
            'weekday' => 7,
            'scope' => BackupScope::All->value,
            'keep' => max(1, min(30, (int) config('backup.keep'))),
        ];
    }

    private function stepDays(string $frequency): int
    {
        return $frequency === 'weekly' ? 7 : 1;
    }

    private function int(mixed $value, int $min, int $max, int $default): int
    {
        $int = filter_var($value, FILTER_VALIDATE_INT);

        return $int !== false && $int >= $min && $int <= $max ? $int : $default;
    }
}
