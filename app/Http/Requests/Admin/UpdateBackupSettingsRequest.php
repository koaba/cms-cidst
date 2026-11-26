<?php

namespace App\Http\Requests\Admin;

use App\Enums\BackupScope;
use App\Services\Backup\BackupSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valide le formulaire "Sauvegarde automatique" (la case envoie 0 ou 1 : champ cache + case). */
class UpdateBackupSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Le filtrage par role est deja assure par le middleware de route (Super Admin).
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'auto' => ['required', 'boolean'],
            'frequency' => ['required', Rule::in(BackupSettings::FREQUENCIES)],
            'weekday' => ['required', 'integer', 'between:1,7'],
            'hour' => ['required', 'integer', 'between:0,23'],
            'scope' => ['required', Rule::enum(BackupScope::class)],
            'keep' => ['required', 'integer', 'between:1,30'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'frequency' => "fr\u{e9}quence",
            'weekday' => 'jour',
            'hour' => 'heure',
            'scope' => 'contenu',
            'keep' => "nombre de sauvegardes conserv\u{e9}es",
        ];
    }
}
