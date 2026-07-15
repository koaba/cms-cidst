# Environnement local — laravel-academy

## Spécificité importante : deux installations PHP (résolu le 15/07/2026)

Cette machine avait historiquement deux PHP installés en conflit :
- Laragon : `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\` (OFFICIEL, à utiliser)
- Ancien : `C:\Dev\Tools\php\` (SUPPRIMÉ du PATH système, ne plus réinstaller)

Le PATH système a été corrigé pour placer Laragon en première position.

Vérifier avec `where.exe php` que seul celui de Laragon apparaît.
(`where` seul ne fonctionne pas dans PowerShell, utiliser `where.exe`)

## php.ini

Chemin exact : `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini`

Extensions confirmées actives : `mysqli`, `pdo_mysql` (déjà activées par
défaut sur l'installation Laragon, rien à configurer manuellement).

## Stack

- PHP : 8.3.30 (ZTS, Visual C++ 2019 x64)
- Laravel : v13.19.0
- MySQL : via Laragon, base `laravel_academy`
- Node/npm : Vite + Tailwind

## Terminal VS Code

Le fichier `.vscode/settings.json` (versionné sur Git, forcé avec `git add -f`)
force le terminal intégré à s'ouvrir directement dans le dossier du projet.

TOUJOURS 2 terminaux séparés dans VS Code (Ctrl+ù puis + pour le second) :
- Terminal 1 "Serveur" : `php artisan serve` (n'y plus rien taper après)
- Terminal 2 "Commandes" : toutes les autres commandes (git, artisan make, npm...)

## Comptes de test

- admin@academy.local / password123 (Super Admin)
- user@academy.local / password123 (sans rôle, test 403)

## Cette config est LOCALE uniquement

Un environnement de production correctement configuré (un seul PHP,
php.ini standard) n'aura pas ces problèmes. Ne pas reproduire ce
setup de double-PHP ailleurs — c'était un accident historique, pas
une architecture voulue.