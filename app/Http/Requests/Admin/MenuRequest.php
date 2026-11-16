<?php

namespace App\Http\Requests\Admin;

use App\Models\Menu;
use App\Rules\SafeLinkUrl;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'target' => ['required', 'string', 'max:255', new SafeLinkUrl],
            'location' => ['nullable', Rule::in(array_keys(Menu::LOCATIONS))],
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'parent_id' => 'nullable|exists:menus,id',
        ];
    }

    /**
     * Le pied de page et les mentions legales n'ont pas de sous-menus ;
     * un sous-menu du menu principal n'a qu'un parent du menu principal.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $parentId = $this->input('parent_id');

            if ($parentId === null || $parentId === '' || $validator->errors()->hasAny(['parent_id', 'location'])) {
                return;
            }

            $location = $this->input('location') ?: ($this->route('menu')?->location ?? Menu::MAIN);

            if ($location !== Menu::MAIN) {
                $validator->errors()->add('parent_id', 'Un lien du pied de page ou des mentions légales ne peut pas avoir de menu parent.');

                return;
            }

            if (Menu::whereKey($parentId)->where('location', '!=', Menu::MAIN)->exists()) {
                $validator->errors()->add('parent_id', 'Le menu parent doit appartenir au menu principal.');
            }
        });
    }

    /**
     * Champs propres au modele Menu, avec leurs valeurs par defaut.
     */
    public function menuData(): array
    {
        $validated = $this->validated();

        return [
            ...(isset($validated['location']) ? ['location' => $validated['location']] : []),
            'label' => $validated['label'],
            'target' => $validated['target'],
            'order' => $validated['order'] ?? 0,
            'is_active' => $this->boolean('is_active'),
            'parent_id' => $validated['parent_id'] ?? null,
        ];
    }
}
