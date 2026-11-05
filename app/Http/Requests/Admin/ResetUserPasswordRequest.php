<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide la reinitialisation du mot de passe d'un utilisateur par un Super Admin.
 */
class ResetUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Le filtrage par role est deja assure par le middleware de route.
        return true;
    }

    public function rules(): array
    {
        return [
            'new_password' => 'required|string|min:8',
        ];
    }
}
