# Environnement local : laravel-academy

## Spécificité importante : deux installations PHP (résolu le 15/07/2026)

Cette machine avait historiquement deux PHP installés en conflit :
- Laragon : `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\` (OFFICIEL, à utiliser)
- Ancien : `C:\Dev\Tools\php\` (SUPPRIMÉ du PATH système, ne plus réinstaller)

Le PATH système a été corrigé pour placer Laragon en première position.

Vérifier avec `where.exe php` que seul celui de Laragon apparaît.
(`where` seul ne fonctionne pas dans PowerShell, utiliser `where.exe`)

## php.ini

Chemin exact : `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini`

Extensions confirmées actives : `mysqli`, `pdo_mysql` (déjà activées par défaut sur l'installation Laragon, rien à configurer manuellement). L'extension `gd` est aussi active (Intervention : miniatures et filigrane).

## Stack

- PHP : 8.3.30 (ZTS, Visual C++ 2019 x64)
- Laravel : ^13.8 (version exacte dans `composer.lock`, v13.35.0 au 08/10/2026)
- MySQL : via Laragon, base `laravel_academy`
- Node/npm : Vite + Tailwind

## Terminal VS Code

Le fichier `.vscode/settings.json` (versionné sur Git, forcé avec `git add -f`) force le terminal intégré à s'ouvrir directement dans le dossier du projet.

TOUJOURS 2 terminaux séparés dans VS Code (Ctrl+` puis + pour le second) :
- Terminal 1 "Serveur" : `php artisan serve` (n'y plus rien taper après)
- Terminal 2 "Commandes" : toutes les autres commandes (git, artisan make, npm...)

## Comptes de test

- `admin@academy.local` (Super Admin)
- `user@academy.local` (sans rôle, test 403)

Les mots de passe se définissent localement et ne sont jamais versionnés.

## Cette config est LOCALE uniquement

Un environnement de production correctement configuré (un seul PHP, php.ini standard) n'aura pas ces problèmes. Ne pas reproduire ce setup de double-PHP ailleurs : c'était un accident historique, pas une architecture voulue.

## Piège : composer dump-autoload silencieux si mauvais dossier

Le 15/07/2026, un nouveau controller (Admin\PageController) restait introuvable ("Target class ... does not exist") malgré un fichier parfaitement valide (syntaxe vérifiée avec php -l).

Cause réelle : composer dump-autoload avait été lancé depuis le terminal Laragon, mais positionné dans C:\laragon\www (dossier PARENT), pas dans C:\laragon\www\laravel-academy. Composer échouait donc silencieusement à trouver composer.json, et le classmap n'était jamais régénéré pour le bon projet.

Vérification après tout "Class ... does not exist" :
1. Confirmer le dossier courant avant de lancer composer
2. Si besoin : cd C:\laragon\www\laravel-academy
3. Relancer : composer dump-autoload -o
4. Vérifier dans le classmap que la classe y apparaît

Note complémentaire : le terminal Laragon utilise Bash/MinGW, pas PowerShell. Utiliser grep (pas Select-String) et des slashs / (pas des antislashs \) pour les chemins dans ce terminal.

## Piège : "dubious ownership" Git dans le terminal Laragon

Le terminal Laragon peut refuser les commandes git avec l'erreur "detected dubious ownership". Correction unique (à faire une fois) :

    git config --global --add safe.directory C:/laragon/www/laravel-academy

## Piège : `migrate:rollback --step=N` annule par ordre chronologique, pas par nom

Si des migrations récentes à supprimer sont mêlées chronologiquement à d'autres, plus récentes encore, qu'on veut garder, un `rollback --step=N` global les mélange toutes dans le même lot sans distinction.

Méthode fiable pour nettoyer des migrations orphelines dans ce cas (vérifiée le 21/07/2026 sur le nettoyage des migrations `hero_pattern_*`) :

1. `php artisan migrate:rollback --step=1` puis `php artisan migrate:status` après CHAQUE étape, un cran à la fois
2. Si une migration à garder est annulée au passage (parce qu'elle est plus récente que celles à supprimer), la réappliquer immédiatement avec `php artisan migrate` avant de continuer
3. Une fois que seules les migrations à supprimer sont `Pending`, supprimer leurs fichiers `.php` du dossier `database/migrations/`
4. Relancer `php artisan migrate` : Laravel ne réappliquera que ce qui reste physiquement sur le disque, donc uniquement ce qu'on veut garder

## Piège : blocage Windows `sf_proc_00.out.lock` (Permission denied)

Symfony Process n'arrive pas toujours à créer son fichier verrou dans `AppData\Local\Temp`. Effets constatés le 08/10/2026 : `php artisan test` plante, et la génération de miniatures PDF (Poppler) échoue sans erreur visible, ce qui met `ArticlePdfTest` au rouge, y compris sur master.

Contournement validé, dans le terminal courant :

    $env:TEMP = "$PWD\storage\framework\tmp"; $env:TMP = $env:TEMP; New-Item -ItemType Directory -Force $env:TEMP | Out-Null

puis lancer `./vendor/bin/pest`. La redirection ne vaut que pour la session du terminal. La cause racine n'est pas identifiée. Ne pas arrêter `php artisan serve`, il n'est pas en cause.

## Astuce : affichage UTF-8 dans le terminal cmd.exe

Par défaut, cmd.exe affiche les caractères accentués déformés (ex : "créé" devient "crâ”œÂ®â”œÂ®"), même si les fichiers source sont correctement encodés en UTF-8. Avant de diagnostiquer un "problème d'encodage", toujours vérifier avec :

    chcp 65001

Cette commande force l'affichage UTF-8 pour la session de terminal en cours.
