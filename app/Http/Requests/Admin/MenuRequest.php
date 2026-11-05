<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide la creation et la modification d'un element de menu : les regles
 * sont identiques, une seule classe sert donc aux deux actions.
 */
class MenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Le filtrage par role est deja assure par le middleware de route.
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => 'required|string|max:255',
            'target' => 'required|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'parent_id' => 'nullable|exists:menus,id',
        ];
    }

    /**
     * Champs propres au modele Menu, avec leurs valeurs par defaut.
     */
    public function menuData(): array
    {
        $validated = $this->validated();

        return [
            'label' => $validated['label'],
            'target' => $validated['target'],
            'order' => $validated['order'] ?? 0,
            'is_active' => $this->boolean('is_active'),
            'parent_id' => $validated['parent_id'] ?? null,
        ];
    }
}
