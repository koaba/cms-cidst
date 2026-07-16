# Environnement local � laravel-academy

## Sp�cificit� importante : deux installations PHP (r�solu le 15/07/2026)

Cette machine avait historiquement deux PHP install�s en conflit :
- Laragon : `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\` (OFFICIEL, � utiliser)
- Ancien : `C:\Dev\Tools\php\` (SUPPRIM� du PATH syst�me, ne plus r�installer)

Le PATH syst�me a �t� corrig� pour placer Laragon en premi�re position.

V�rifier avec `where.exe php` que seul celui de Laragon appara�t.
(`where` seul ne fonctionne pas dans PowerShell, utiliser `where.exe`)

## php.ini

Chemin exact : `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini`

Extensions confirm�es actives : `mysqli`, `pdo_mysql` (d�j� activ�es par
d�faut sur l'installation Laragon, rien � configurer manuellement).

## Stack

- PHP : 8.3.30 (ZTS, Visual C++ 2019 x64)
- Laravel : v13.19.0
- MySQL : via Laragon, base `laravel_academy`
- Node/npm : Vite + Tailwind

## Terminal VS Code

Le fichier `.vscode/settings.json` (versionn� sur Git, forc� avec `git add -f`)
force le terminal int�gr� � s'ouvrir directement dans le dossier du projet.

TOUJOURS 2 terminaux s�par�s dans VS Code (Ctrl+� puis + pour le second) :
- Terminal 1 "Serveur" : `php artisan serve` (n'y plus rien taper apr�s)
- Terminal 2 "Commandes" : toutes les autres commandes (git, artisan make, npm...)

## Comptes de test

- admin@academy.local / password123 (Super Admin)
- user@academy.local / password123 (sans r�le, test 403)

## Cette config est LOCALE uniquement

Un environnement de production correctement configur� (un seul PHP,
php.ini standard) n'aura pas ces probl�mes. Ne pas reproduire ce
setup de double-PHP ailleurs � c'�tait un accident historique, pas
une architecture voulue.
## Piège : composer dump-autoload silencieux si mauvais dossier

Le 15/07/2026, un nouveau controller (Admin\PageController) restait
introuvable ("Target class ... does not exist") malgré un fichier
parfaitement valide (syntaxe vérifiée avec php -l).

Cause réelle : composer dump-autoload avait été lancé depuis le
terminal Laragon, mais positionné dans C:\laragon\www (dossier
PARENT), pas dans C:\laragon\www\laravel-academy. Composer échouait
donc silencieusement à trouver composer.json, et le classmap n'était
jamais régénéré pour le bon projet.

Vérification après tout "Class ... does not exist" :
1. Confirmer le dossier courant avant de lancer composer
2. Si besoin : cd C:\laragon\www\laravel-academy
3. Relancer : composer dump-autoload -o
4. Vérifier dans le classmap que la classe y apparaît

Note complémentaire : le terminal Laragon utilise Bash/MinGW, pas
PowerShell. Utiliser grep (pas Select-String) et des slashs / (pas
des antislashs \) pour les chemins dans ce terminal.

## Piège : "dubious ownership" Git dans le terminal Laragon

Le terminal Laragon peut refuser les commandes git avec l'erreur
"detected dubious ownership". Correction unique (à faire une fois) :

git config --global --add safe.directory C:/laragon/www/laravel-academy