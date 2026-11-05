<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide l'enveloppe envoyee par le Page Builder : la liste des blocs racine
 * et la version de reference. Le contenu de chaque bloc est valide plus loin,
 * par les regles de son type (BlockSyncService).
 */
class SaveBuilderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Le filtrage par role est deja assure par le middleware de route.
        return true;
    }

    public function rules(): array
    {
        return [
            'blocks' => ['present', 'array', 'max:200'],
            'blocks.*' => ['array'],
            'blocks.*.id' => ['nullable', 'integer'],
            'blocks.*.type' => ['required', 'string'],
            'blocks.*.data' => ['nullable', 'array'],
            'blocks.*.media_id' => ['nullable', 'integer'],
            'version' => ['nullable', 'string'],
        ];
    }
}
