<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * Valide la creation et la modification d'un utilisateur. Seules deux regles
 * different : le mot de passe (creation uniquement) et l'unicite de l'e-mail
 * (qui ignore le compte modifie). Une seule classe sert donc aux deux actions.
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Le filtrage par role est deja assure par le middleware de route.
        return true;
    }

    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::exists((new Role)->getTable(), 'name')],
            ...($user ? [] : ['password' => 'required|string|min:8']),
        ];
    }
}
