# Configuration requise

Ce document decrit ce dont un serveur a besoin pour heberger l'application, et ce qui se degrade proprement quand un element manque.

## Controle automatique

Avant toute mise en ligne, lancer a la racine du projet :

    php artisan app:check-environment

Chaque ligne est `OK`, `AVERTISSEMENT` (fonctionnalite degradee, le site marche) ou `ERREUR` (le site ne peut pas fonctionner). Le code de sortie est 1 s'il y a au moins une erreur, 0 sinon.

Limite connue : la commande lit le `php.ini` de la ligne de commande. Sur un hebergement, `memory_limit`, `max_execution_time`, `upload_max_filesize` et `post_max_size` peuvent etre differents cote serveur web. Les verifier aussi avec un `phpinfo()` temporaire, a supprimer immediatement apres.

## Minimum (obligatoire)

| Element | Exigence |
|---|---|
| PHP | 8.3.0 ou plus (Laravel 13 exige 8.3 au minimum) |
| Extensions | curl, dom, fileinfo, filter, hash, iconv, json, libxml, openssl, pcre, phar, session, simplexml, tokenizer, xml, xmlwriter, zlib |
| Images | gd avec support WebP |
| Base de donnees | pdo_mysql ou pdo_sqlite (version minimale de MySQL/MariaDB : voir la documentation officielle de Laravel 13, section Database) |
| Dossiers inscriptibles | `storage/` et `bootstrap/cache/` |
| memory_limit | 128M minimum (`-1` accepte) |
| max_execution_time | 30 s minimum (`0` accepte) ; indispensable avec `QUEUE_CONNECTION=sync`, car les variantes d'images sont alors generees pendant l'envoi |

## Recommande

- Un worker de file (`QUEUE_CONNECTION=database`) : sans worker, aucune variante d'image n'est generee.
- `pdftoppm` (Poppler) ou l'extension Imagick avec Ghostscript pour les miniatures PDF cote serveur.
- `memory_limit` de 256M.
- `upload_max_filesize` et `post_max_size` adaptes au poids des PDF publies (a fixer quand ce poids sera connu).

## Elements optionnels et degradations

| Absent | Consequence |
|---|---|
| `proc_open` desactive, ou ni `pdftoppm` ni Imagick | Pas de miniature PDF cote serveur. La miniature generee par le navigateur (pdf.js) reste utilisee. Un envoi de PDF n'echoue jamais pour cette raison. |
| Pilote `PDF_THUMBNAIL_DRIVER` inconnu | Journalise un avertissement et desactive la miniature serveur (comme `none`). |
| Image de moins de 480 px de large | Pas de variante, l'image d'origine est servie. |

Valeurs de `PDF_THUMBNAIL_DRIVER` : `poppler` (defaut), `imagick`, `none`.

## Production

- `APP_ENV=production` et `APP_DEBUG=false`.
- `APP_KEY` generee une seule fois et conservee hors du serveur. Ne jamais lancer `key:generate` sur un site en service.
- Construire les assets (`npm run build`) avant l'envoi : un hebergement partage n'a en general pas Node.
- Installer avec `composer install --no-dev --optimize-autoloader`.
- Sauvegarde datee de la base avant toute migration, copie hors serveur, restauration testee.
- Si les liens symboliques sont interdits, `php artisan storage:link` ne fonctionnera pas : prevoir une alternative (point a traiter au choix de l'hebergeur).

## Mises a jour de PHP

- `composer.json` verrouille la resolution des paquets sur PHP 8.3.0 (`config.platform.php`) : le dossier `vendor/` reste valable sur tout serveur PHP 8.3 ou plus recent.
- Avant de passer le serveur a une version plus recente de PHP, lancer la suite de tests sur cette version (la CI le fait automatiquement) puis `php artisan app:check-environment` sur le serveur.
- Ne jamais lancer `composer update` directement sur le serveur de production.

## Points non verifies

- Version minimale de MySQL/MariaDB exigee par Laravel 13.
- Comportement des files d'attente en mode worker avec le cache des reglages du site.
