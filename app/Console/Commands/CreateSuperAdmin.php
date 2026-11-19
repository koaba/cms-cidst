<?php

namespace App\Console\Commands;

use App\Services\SuperAdminCreator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateSuperAdmin extends Command
{
    protected $signature = 'app:create-super-admin {email : Adresse e-mail du compte} {--name=Super Admin : Nom affiche} {--generate : Genere un mot de passe aleatoire au lieu de le demander}';

    protected $description = 'Cree le premier Super Admin (et les roles) d\'une installation vierge';

    public function handle(SuperAdminCreator $creator): int
    {
        $email = (string) $this->argument('email');

        if ($creator->exists()) {
            $this->error('Un Super Admin existe deja : creez les autres comptes depuis l\'administration (menu Utilisateurs).');

            return self::FAILURE;
        }

        $generated = (bool) $this->option('generate');
        $password = $generated ? Str::password(16) : (string) $this->secret('Mot de passe');

        if (! $generated && $password !== (string) $this->secret('Confirmez le mot de passe')) {
            $this->error('Les deux mots de passe sont differents.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', Password::defaults()]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $creator->create((string) $this->option('name'), $email, $password);

        $this->info("Super Admin cree : {$email}. Le mot de passe devra etre change a la premiere connexion.");

        if ($generated) {
            $this->line("Mot de passe genere (affiche une seule fois) : {$password}");
        }

        return self::SUCCESS;
    }
}
