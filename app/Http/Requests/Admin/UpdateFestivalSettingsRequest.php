<?php

namespace App\Http\Requests\Admin;

use App\Services\Festival\FestivalSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide le formulaire Administration > Fetes. Les cases a cocher envoient 0 ou 1 (champ cache + case).
 */
class UpdateFestivalSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Le filtrage par role est deja assure par le middleware de route (Super Admin).
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $icon = ['nullable', 'string', Rule::in(array_keys((array) config('festivals.icons', [])))];

        return [
            'festivals_enabled' => ['required', 'boolean'],
            'festivals_simulated_on' => ['nullable', 'date_format:Y-m-d'],
            'festivals' => ['nullable', 'array:'.implode(',', array_keys((array) config('festivals.festivals', [])))],
            'festivals.*' => ['array'],
            'festivals.*.enabled' => ['nullable', 'boolean'],
            'festivals.*.placement' => ['nullable', Rule::in(array_keys(FestivalSettings::PLACEMENTS))],
            'festivals.*.logo' => $icon,
            'festivals.*.strip' => ['nullable', 'array', 'max:'.FestivalSettings::STRIP_SLOTS],
            'festivals.*.strip.*' => $icon,
            'festivals.*.animated' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'festivals_simulated_on' => 'date de simulation',
            'festivals.*.placement' => 'emplacement',
            'festivals.*.logo' => "ic\u{f4}ne du logo",
            'festivals.*.strip' => "ic\u{f4}nes du bandeau",
            'festivals.*.strip.*' => "ic\u{f4}ne du bandeau",
        ];
    }
}
