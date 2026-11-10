<?php

namespace App\Http\Requests\Admin;

use App\Rules\SafeLinkUrl;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide la creation et la modification d'une actualite : les regles sont
 * identiques, une seule classe sert donc aux deux actions.
 */
class NewsTickerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Le filtrage par role est deja assure par le middleware de route.
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:255',
            'link_url' => ['nullable', 'string', 'max:255', new SafeLinkUrl],
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
        ];
    }
}
