# CMS CIDST

CMS Laravel modulaire : pages composées de blocs, articles, sliders, médiathèque, documents PDF, SEO (sitemap, robots) et tableau de bord d'administration.

## Prérequis

- PHP ^8.3 (8.3.30 en développement), Composer
- Laravel ^13.8, Pest ^4.7, Pint, `intervention/image` ^4.2
- MySQL (base `laravel_academy` en local)
- Extension PHP `gd`, binaire Poppler `pdftoppm` (miniatures PDF) et un worker de file d'attente (`php artisan queue:work`) en production : voir [DEPLOYMENT.md](DEPLOYMENT.md)
- Node.js et npm (Vite, Tailwind, daisyUI)

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
# renseigner la base de données dans .env
php artisan migrate
php artisan storage:link
npm install
npm run dev
```

Détails de l'environnement local (Laragon, PHP, terminaux, pièges connus) : [ENVIRONMENT.md](ENVIRONMENT.md).

## Tests et qualité

```bash
./vendor/bin/pest
./vendor/bin/pint
```

Sous Windows, si les tests échouent sur `sf_proc_00.out.lock` (Permission denied), rediriger le dossier temporaire de la session vers `storage/framework/tmp` avant de lancer Pest.

## Commandes Artisan du projet

| Commande | Rôle |
|---|---|
| `media:prune` | Supprime les médias liés à aucun contenu |
| `media:backfill-dimensions [--dry-run]` | Renseigne `width` et `height` des images existantes (idempotente) |
| `sliders:migrate-to-media [--dry-run]` | Migre les images de sliders vers la médiathèque |
| `app:migrate-videos-to-media [--dry-run]` | Migre les vidéos vers la médiathèque |
| `blocks:audit-phantom-columns` | Audit des blocs colonnes (voir `AuditPhantomColumnBlocks`) |

## Architecture

- Une page est composée de `PageBlock` ordonnés et imbriquables. Chaque type de bloc a une classe de règles (`app/Blocks/Rules`), une entrée dans `BlockRegistry`, un partial admin et un partial public.
- Le traitement des fichiers de blocs est centralisé dans `BlockMediaService`. La lecture des dimensions d'image passe par `MediaDimensionsReader`.
- Chargement des images publiques : règles imposées par `tests/Feature/PublicImgLoadingTest.php`.
- Le sitemap est mis en cache et invalidé par `SitemapCacheObserver`.

## Mise en production

Voir [DEPLOYMENT.md](DEPLOYMENT.md). La liste doit être entièrement cochée avant toute ouverture au public.

## Feuille de route

Voir [CMS-ROADMAP-RECOMMANDATIONS.md](CMS-ROADMAP-RECOMMANDATIONS.md).

## Contribution

Branches courtes (`feat/`, `fix/`, `perf/`, `chore/`), fusionnées en `--no-ff`. Avant chaque commit : Pint, puis tests. Tout test nouveau est vérifié par mutation.
