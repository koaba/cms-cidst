# CMS CIDST

CMS Laravel modulaire : pages composées de blocs, articles, sliders, médiathèque, documents PDF, SEO (sitemap, robots) et tableau de bord d'administration. Design moderne et responsive (mobile-first), administration très personnalisable, portable sur hébergement mutualisé.

> Ce fichier remplace l'ancien `README.md`, `ENVIRONMENT.md`, `DEPLOYMENT.md` et `CMS-ROADMAP-RECOMMANDATIONS.md`. Dernière mise à jour : 21/10/2026.
> `[non vérifié]` désigne ce qu'aucune sortie ni lecture du code n'a confirmé.

## Sommaire

1. [Principes du projet](#1-principes-du-projet)
2. [Stack](#2-stack)
3. [Installation](#3-installation)
4. [Architecture et découpage](#4-architecture-et-découpage)
5. [Conventions de développement](#5-conventions-de-développement)
6. [Environnement local Windows / Laragon](#6-environnement-local-windows--laragon)
7. [Tests et intégration continue](#7-tests-et-intégration-continue)
8. [Sécurité](#8-sécurité)
9. [Déploiement](#9-déploiement)
10. [Feuille de route](#10-feuille-de-route)

---

## 1. Principes du projet

- **Propre et sans redondance** : la logique métier vit dans des services, des traits et des composants Blade, jamais dupliquée dans les contrôleurs.
- **Évolutif** : chaque fonctionnalité est une partie isolée, testée, que l'on peut faire évoluer sans toucher aux autres.
- **Portable** : PHP 8.3+, hébergeur limité accepté. Aucun SQL propre à un moteur : les regroupements se font en PHP.
- **Design** : adaptatif mobile-first, harmonisé entre site public et administration, administration personnalisable (couleurs, thème), SEO soigné.
- **Pas de sur-architecture** : les patterns génériques sont introduits au fil de l'eau quand l'occasion se présente naturellement. Réévaluation complète prévue en fin de projet.
- **Aucun déploiement avant la fin du projet.**

## 2. Stack

| Domaine | Choix |
|---|---|
| Backend | Laravel 13 (13.35.0 au 08/10/2026, version exacte dans `composer.lock`), PHP 8.3 |
| Base de données | MySQL en local (base `laravel_academy`), SQLite mémoire pour les tests |
| Droits | `spatie/laravel-permission` (rôles : Super Admin, Publication, …) |
| Images | Intervention Image 4 (GD) : miniatures, filigrane, variantes |
| PDF | TCPDF / FPDI, miniatures via Poppler (`pdftoppm`) |
| Front | Tailwind 3, DaisyUI 4.12.2 (thème `cidst`, `darkTheme: false`), `@tailwindcss/forms`, Alpine, Chart.js, Vite 8 |
| Tests / qualité | Pest 4, Pint, hooks Husky, GitHub Actions |

Extensions PHP requises en plus de celles de Laravel : `pdo_mysql`, `gd`.

## 3. Installation

Poste neuf (commandes standard) :

```bash
git clone https://github.com/koaba/cms-cidst.git
cd cms-cidst
composer install
cp .env.example .env
php artisan key:generate
npm ci
php artisan migrate
php artisan storage:link
npm run build
./vendor/bin/pest --compact
```

Renseigner la base de données dans `.env` avant `php artisan migrate`. En développement, remplacer `npm run build` par `npm run dev`.

Points d'attention :

- `APP_URL` local : `http://laravel-academy.test` (Laragon).
- Poppler doit être installé pour les miniatures PDF (`PDF_THUMBNAIL_POPPLER_BINARY` pour un chemin personnalisé, voir `.env.example`). Sans lui, l'échec est silencieux : seul `storage/logs/laravel.log` le signale.
- File d'attente : `QUEUE_CONNECTION=database` par défaut. Sans worker (`php artisan queue:work`), les miniatures ne sont jamais générées.
- Après la migration : `php artisan media:backfill-dimensions` renseigne les dimensions des images existantes (idempotente, `--dry-run` pour simuler).

Comptes de test locaux : `admin@academy.local` (Super Admin) et `user@academy.local` (sans rôle, pour tester le 403). Les mots de passe se définissent localement et ne sont jamais versionnés.

## 4. Architecture et découpage

### Parties existantes

| Partie | Rôle |
|---|---|
| `Services\Dashboard\DashboardPeriod` | Période du tableau de bord : `week`, `month` (défaut), `year`, `all` (plafonné à 60 mois). Variation `null` si la période précédente vaut 0. |
| `Services\Theme\ColorConverter` | Convertit `#RRGGBB` en triplet RGB (Tailwind) et en OKLCH (DaisyUI 4), avec la couleur de contraste. Base du thème personnalisable. |
| `MediaOverviewService` | Vue d'ensemble de la médiathèque (compteurs, alertes, derniers ajouts) et règle `isInUse(Media)`. |
| `MediaSyncService` | Synchronisation des couvertures et images liées aux contenus. |
| Traits `HasOrphanMediaCleanup`, `HasOrderedMediaCollection` | Nettoyage des médias orphelins, collections de médias ordonnées. |
| `Middleware\SecurityHeaders` | En-têtes de sécurité HTTP, appliqué au groupe `web` dans `bootstrap/app.php`. |
| Middleware `track.view` | Suivi des vues (aujourd'hui limité à `blog.show`). |

### Pages composées de blocs

- Une page est composée de `PageBlock` ordonnés et imbriquables. Chaque type de bloc a une classe de règles (`app/Blocks/Rules`), une entrée dans `BlockRegistry`, un partial admin et un partial public.
- Le traitement des fichiers de blocs est centralisé dans `BlockMediaService`. La lecture des dimensions d'image passe par `MediaDimensionsReader`.
- Chargement des images publiques : règles imposées par `tests/Feature/PublicImgLoadingTest.php`.
- Le sitemap est mis en cache et invalidé par `SitemapCacheObserver`.

### Commandes Artisan du projet

| Commande | Rôle |
|---|---|
| `media:prune` | Supprime les médias liés à aucun contenu |
| `media:backfill-dimensions [--dry-run]` | Renseigne `width` et `height` des images existantes (idempotente) |
| `sliders:migrate-to-media [--dry-run]` | Migre les images de sliders vers la médiathèque |
| `app:migrate-videos-to-media [--dry-run]` | Migre les vidéos vers la médiathèque |
| `blocks:audit-phantom-columns` | Audit des blocs colonnes (voir `AuditPhantomColumnBlocks`) |

### Règle « média utilisé »

Un média est utilisé si l'un de ces quatre cas est vrai (quatre `exists()` portables dans `MediaOverviewService::isInUse`) : présent dans le pivot `mediables`, `articles.cover_media_id`, `articles.image = media.path`, `seo_metas.og_image = media.path`. Cette règle protège toute suppression de fichier.

Limite connue : le format de `og_image` (chemin ou URL) n'est pas vérifié. Une URL donnerait un faux négatif.

### Faits à connaître sur les médias

- L'événement `deleting` de `Media` supprime fichier, miniature et variantes. `Media::query()->delete()` groupé ne le déclenche pas : boucler sur les modèles.
- `Media::usages()` fait un N+1 : ne pas l'utiliser en liste.
- Filtrer sur `mime_type`, jamais sur `type` (enum).
- `mediables` est en cascade sur `media_id` ; `articles.cover_media_id` est en `nullOnDelete`.
- Les sliders passent par le pivot et par `sliders.image = media.path`.

### Thème et design

- Tailwind lit `cidst-red` / `cidst-ink` via `var(--color-primary, #C41E2A)` et `var(--color-secondary, #17181A)`. Aujourd'hui rien ne définit ces variables : le repli hexadécimal est toujours utilisé.
- Thème injecté par `<x-theme-style />` (layouts public et admin, après `@vite`) : `html:root{--p;--pc;--a;--ac;--s;--sc;--n;--nc;--color-primary-rgb;--color-secondary-rgb}`, généré par `ThemeStyleBuilder` depuis `getRawOriginal()` de `SiteSetting` (valeur invalide : repli sur `#C41E2A` / `#17181A`). Ne pas utiliser les accesseurs `primary_color` / `secondary_color` (`#000000` / `#FFFFFF` si invalide, figés par `SiteSettingColorTest`). Tailwind : `cidst.red` et `cidst.ink` lisent `rgb(var(--color-primary-rgb) / <alpha-value>)`.
- Défauts en base : `#E80000` / `#1A1A1A`, différents de ceux du thème DaisyUI (`#C41E2A` / `#17181A`). Décision recommandée : migration qui ne remplace que les lignes encore à l'ancien défaut.
- Variables admin : 4 variables `--admin-*` dans `app.css`.
- Règle de chaque lot design : mobile-first, paliers Tailwind par défaut, vérification à 360, 768, 1024, 1440 et 1920 px.

### SEO

Existant : sitemap, `seo_metas`, en-têtes de sécurité. À compléter : sitemap complet, JSON-LD (Article, WebPage), canonical des listes, `robots.txt` dynamique, polices auto-hébergées.

## 5. Conventions de développement

- **Code** : un trait ou service par responsabilité. Les validations vont dans des `FormRequest` dédiés (jamais `$request->validate()` inline), avec `exists:table,id` pour toute liste d'identifiants (`sync`, `attach`).
- **Modèles** : `$fillable` strict ; `user_id` toujours assigné côté serveur.
- **Slugs et visibilité** : un seul générateur de slug (trait `HasSlug`) et un seul scope `publiclyVisible` (`is_published`, `published_at` non nul et `<= now()`) pour tout contenu publiable. `NULL <= now()` est toujours faux : un `published_at` nul rend le contenu invisible sans erreur. `[à vérifier : traits créés ou non]`
- **Fichiers** : UTF-8 sans BOM, LF (préserver CRLF sur un fichier existant, par exemple `sidebar.blade.php`).
- **Git** : branches courtes (`feat/`, `fix/`, `perf/`, `chore/`, `docs/`), fusionnées en `--no-ff`. Commits sémantiques (`feat:`, `fix:`, `refactor:`, `docs:`, `test:`). `git add` fichier par fichier. Ordre : Pint, Pest, commit. Merge `--no-ff`, push, `git branch -d`, `git status -sb`.
- **Tests** : test rouge d'abord, sur échec d'assertion (jamais sur une erreur PHP), puis correctif, puis mutation de contrôle d'une ligne.
- **Front** : CSS et JS non couverts par Pest : `npm run build` puis vérification visuelle (Ctrl+F5).
- **Helpers Pest globaux** : ils vivent dans l'espace global, donc un nom déjà pris provoque une erreur. Préfixer chaque helper par son domaine (`colorConv…`, `inUse…`, `dash…`) et vérifier le nom avant d'en ajouter.
- **Tests créant des `Media`** : toujours `Queue::fake()` ou `Bus::fake()`.
- **Ne pas migrer le hero public** (`BlockHeroMediaImgTest`).
- **Ne pas réécrire l'historique de `master`.**

## 6. Environnement local Windows / Laragon

### PHP

Un seul PHP doit être actif : celui de Laragon (`C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\`). L'ancien `C:\Dev\Tools\php\` a été retiré du PATH : ne pas le réinstaller. Vérifier avec `where.exe php` (pas `where`, qui est un alias PowerShell). Ce double-PHP était un accident historique, à ne jamais reproduire en production.

### Terminaux VS Code

`.vscode/settings.json` est versionné (forcé avec `git add -f`) et ouvre le terminal dans le dossier du projet. Toujours deux terminaux : « Serveur » (`php artisan serve`, rien d'autre dedans) et « Commandes » (git, artisan, npm).

Le terminal Laragon est Bash/MinGW (`grep`, slashs `/`), alors que celui de VS Code est PowerShell 5.

### Pièges connus

| Piège | Remède |
|---|---|
| `composer dump-autoload` silencieux : lancé depuis `C:\laragon\www` au lieu du dossier du projet, la classe reste introuvable | Vérifier le dossier courant, `cd C:\laragon\www\laravel-academy`, puis `composer dump-autoload -o` et contrôler le classmap |
| Git « dubious ownership » dans le terminal Laragon | `git config --global --add safe.directory C:/laragon/www/laravel-academy` (une seule fois) |
| `migrate:rollback --step=N` annule par ordre chronologique, pas par nom | Annuler un cran à la fois, `migrate:status` après chaque étape, réappliquer ce qui doit rester, supprimer les fichiers orphelins puis `migrate` |
| `sf_proc_00.out.lock` (Permission denied) : `artisan test` plante, les miniatures PDF (Poppler) échouent sans erreur | `$env:TEMP = "$PWD\storage\framework\tmp"; $env:TMP = $env:TEMP; New-Item -ItemType Directory -Force $env:TEMP \| Out-Null` puis `./vendor/bin/pest`. Valable pour la session du terminal. Cause racine non identifiée (hypothèse : verrou sur le dossier temporaire). Explique aussi les échecs dispersés sur les variantes d'images (`ImageVariantService`, `GenerateMediaVariants`) et sur `MediaSharedUsageTest` (ligne 94). |
| Accents déformés dans cmd.exe (« créé » devient « crÃ©Ã© ») : faux problème d'encodage | `chcp 65001` avant de diagnostiquer |
| `findstr` et `type` peu fiables pour juger l'encodage ou le contenu d'un fichier | `Select-String` ou une vérification hex via Tinker |
| PowerShell 5 : `Select-String` n'a pas `-Recurse` ; lecture sans `-Encoding UTF8` affiche « Ã© » ; `@(@("a","b"))` aplatit ; pas de `String.Replace` à 3 arguments | `Get-ChildItem -Recurse \| Select-String …`, toujours `-Encoding UTF8` |
| Verrous de fichiers Windows (antivirus, VPN, indexation) sur `storage/framework/testing` | Fermer les processus concernés, en dernier recours redémarrer |
| `Vite manifest not found` apparu une fois en local, transitoire | Côté tests, `withoutVite()` dans `tests/TestCase.php` |

Règle générale : une confirmation verbale ne vaut pas sauvegarde disque. Revérifier un fichier par lecture indépendante avant de le considérer terminé.

En cas d'erreur serveur inexpliquée, lire `storage/logs/laravel.log` (heure UTC = heure locale moins 3 h).

## 7. Tests et intégration continue

- **Local** : `./vendor/bin/pest --compact` (environ 2 min). 524 tests (1278 assertions) à `68b9aba` ; des tests ont été ajoutés depuis, relever le total actuel.
- **Hooks Husky** : pre-commit `php -l` ; pre-push `pint --test` puis `pest --compact`.
- **GitHub Actions** (`.github/workflows/tests.yml`) : push et pull_request, matrice PHP 8.3 / 8.4 / 8.5, Poppler installé, Pint sur 8.3 seulement. Résultat des derniers runs `[non vérifié]`.
- Minimum attendu par module dans `tests/Feature` : visibilité publique, validation des entrées sensibles, suppression en cascade des fichiers.
- À ajouter en CI : `composer audit --abandoned=report` et Larastan (niveau ≥ 5).

## 8. Sécurité

Déjà en place :

- En-têtes HTTP (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy) via `SecurityHeaders`.
- Limitation des tentatives de connexion (5 par e-mail + IP, natif Breeze).
- Politique de mot de passe renforcée dans `AppServiceProvider`, active automatiquement en production.
- `$fillable` strict sur les modèles principaux.
- Validation des catégories (`exists:categories,id`) dans `ArticleController`.

À prévoir :

- 2FA pour les comptes Super Admin.
- Scan de dépendances automatisé (`composer audit` en CI, Dependabot ou Renovate).
- Procédure documentée de rotation d'`APP_KEY`.
- Politique de suppression compatible RGPD (export et suppression sur demande) si déploiement en Europe ou en administration.
- Content-Security-Policy, à évaluer si des scripts ou styles externes sont chargés.
- Paquet abandonné connu : `setasign/fpdi-tcpdf`, sans remplaçant suggéré (backlog). `composer audit` renvoie donc le code 1 : utiliser `--abandoned=report`.

## 9. Déploiement

> Ne pas ouvrir le site au public tant que cette liste n'est pas cochée. Rien n'est déployé avant la fin du projet ; ne pas ouvrir `chore/deploy-shared`.

### Critique : sécurité

- [ ] `APP_DEBUG=false`
- [ ] HTTPS actif (Let's Encrypt), `APP_URL=https://…`, redirection HTTP vers HTTPS
- [ ] `APP_KEY` propre à la production (`php artisan key:generate`), jamais celle du développement
- [ ] Mots de passe forts pour tous les comptes existants (`admin@academy.local` et `user@academy.local` ont des mots de passe simples, posés via Tinker)
- [ ] `composer audit --abandoned=report` sans avis de sécurité juste avant la mise en ligne (dernier contrôle : 08/10/2026, aucun avis)
- [ ] `.env` absent de Git ; `.env.example` sans valeur sensible

### Important : serveur

- [ ] Base de données de production configurée
- [ ] PHP ≥ 8.3, puis `composer check-platform-reqs`. Validé en local sur 8.3.30 ; la CI vise aussi 8.4 et 8.5 `[non vérifié]`
- [ ] Extension `gd` ; binaire `pdftoppm` (paquet `poppler-utils`)
- [ ] Worker de file supervisé (Supervisor ou systemd) : `php artisan queue:work`, et `php artisan queue:restart` à chaque déploiement
- [ ] `php artisan migrate --force`, puis `php artisan media:backfill-dimensions`
- [ ] `config:cache`, `route:cache`, `view:cache`
- [ ] Permissions de `storage/` et `bootstrap/cache/`
- [ ] `npm run build` (pas de `npm run dev`)
- [ ] Sauvegardes automatisées de la base **et** des fichiers (`storage/app/public`, médiathèque), idéalement chiffrées
- [ ] `LOG_LEVEL` adapté, avec rotation des logs

### À considérer

- [ ] `expose_php = Off` dans `php.ini` (masque `X-Powered-By: PHP/8.3.30`)
- [ ] Content-Security-Policy
- [ ] Endpoint de santé (`/up`, natif Laravel) pour la supervision externe

## 10. Feuille de route

### Chantiers en cours et backlog (ordre proposé)

1. Finir `feat/admin-design-dashboard` : thème personnalisable (bloc `:root`, alignement Tailwind, défauts en base), composants Blade (card, stat, badge, empty-state, page-header), dashboard recomposé avec « À traiter » cliquable, filtres médias `?filter=unused|heavy|no_alt`, colonne « Vues » dans la liste des articles. Ensuite : sidebar (icônes, groupes, `aria-current`), migration des `gray-*` (environ 104 occurrences dans 34 fichiers), mode sombre.
2. Suppression des médias inutilisés (Super Admin, refus si `isInUse`, boucle sur les modèles).
3. Suivi des visites : filtre des robots (test d'abord), puis accueil et pages. Seule `blog.show` porte `track.view:article`, donc les chiffres sont incomplets et possiblement gonflés.
4. Années civiles `?year=2025` (décision en attente ; recommandation : année en cours jusqu'au mois courant, logique dans `DashboardPeriod`).
5. Polices auto-hébergées, SEO complet (sitemap, JSON-LD, canonical), rapport CSV, purge de `page_views`.
6. Déploiement, en dernier.

### Dette connue

- `PageController` admin gère encore `image` alors que `pages.image` est supprimée par migration `[non vérifié]`.
- `resources/views/dashboard.blade.php` probablement orphelin (la route `/dashboard` redirige) `[non vérifié]`.
- La transaction de `MediaSyncService` / `ArticleController` ne protège pas le disque (fichier supprimé avant un échec possible).
- `layout.blade.php` : tiroir mobile en JS inline, sans Échap, `aria-expanded` ni focus piégé ; pas de `max-w` sur `<main>`.
- Safelist héritée de Breeze dans `tailwind.config.js` à auditer.
- Graphiques : `<canvas height="90">` sans conteneur de hauteur fixe, d'où le graphique écrasé en `?period=all`.
- `admin/media/index.blade.php` : N+1, `asset('storage/'.$path)` cassé pour vidéos externes et PDF.
- Sous-menus (dropdown) du module Menus : `parent_id` nullable + logique récursive.

### Évolutions futures (ne pas démarrer maintenant)

| Ajout | Phase | Justification | Statut |
|---|---|---|---|
| `spatie/laravel-medialibrary` (à évaluer face à la solution maison) | 2 | Conversions, compteur de références, disques multiples déjà gérés | Non démarré |
| Traits `HasSlug`, `HasPublicVisibility` | 1 | Supprime la duplication observée entre modules | À vérifier |
| CI/CD (lint, tests, audit) | 1 | Moins cher dès le début qu'en rattrapage | Tests + Pint en place ; Larastan et audit à ajouter |
| 2FA Super Admin | 4 | Standard attendu en contexte institutionnel | Non démarré |
| Sitemap XML + `robots.txt` dynamique | 3 | Basique SEO | Sitemap partiel |
| Données structurées Schema.org | 3 | Améliore l'indexation | Non démarré |
| Accessibilité WCAG AA | 3 | Souvent obligatoire en marchés publics / universités | Non démarré |
| i18n / multi-langue (colonne `locale`) | 1 | Coûteux à ajouter après coup | Non démarré |
| Health-check | 4 | Supervision externe | Natif `/up` |
| Logs structurés JSON (mode Enterprise) | 4/5 | Ingestion par un outil de supervision | Non démarré |
| Chiffrement des sauvegardes | 4 | Basique de sécurité | Non démarré |
| Docker Compose de référence (dev + prod) | 5 | Onboarding et compatibilité hébergement | Non démarré |

Architecture cible déjà actée : modes Lite / Standard / Enterprise avec détection automatique selon le serveur, outils de développement (Debugbar, Telescope) strictement séparés de la production, phases fondations, contenu, présentation, sécurité/maintenance, optimisation.
