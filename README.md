# CMS CIDST

CMS Laravel modulaire : pages composées de blocs, articles, sliders, médiathèque, documents PDF, SEO (sitemap, robots, JSON-LD), thèmes et tableau de bord d'administration. Design moderne et responsive (mobile-first), administration très personnalisable, portable sur hébergement mutualisé.

> Ce fichier remplace l'ancien `README.md`, `ENVIRONMENT.md`, `DEPLOYMENT.md` et `CMS-ROADMAP-RECOMMANDATIONS.md`. Dernière mise à jour : 05/11/2026 (HEAD `4d1da21`).
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
9. [Exploitation](#9-exploitation)
10. [Déploiement](#10-déploiement)
11. [Feuille de route](#11-feuille-de-route)

---

## 1. Principes du projet

- **Propre et sans redondance** : la logique métier vit dans des services, des traits et des composants Blade, jamais dans les contrôleurs.
- **Évolutif** : chaque fonctionnalité est une partie isolée, testée, que l'on peut faire évoluer sans toucher aux autres.
- **Portable** : PHP 8.3+, hébergeur limité accepté. Aucun SQL propre à un moteur (voir `Support/SqlDate`), aucun Redis requis.
- **Une seule source de vérité par notion** : rôles dans `App\Enums\UserRole`, textes SEO dans `config/seo.php`, thèmes dans `config/themes.php`.
- **Design** : adaptatif mobile-first, harmonisé entre site public et administration, administration personnalisable (couleurs, thème), SEO soigné.
- **Pas de sur-architecture** : un patron générique n'est introduit que si un besoin réel le justifie.
- **Aucun déploiement avant la fin du projet.**

## 2. Stack

| Domaine | Choix |
|---|---|
| Backend | Laravel 13 (13.35.0, version exacte dans `composer.lock`), PHP 8.3 |
| Base de données | MySQL en local et production visée (base `laravel_academy`) ; SQLite en mémoire pour les tests ; MySQL 8.4 en CI |
| Droits | `spatie/laravel-permission` (rôles via l'enum `UserRole` : Super Admin, Publication) |
| Images | Intervention Image 4 (GD) : miniatures, filigrane, variantes |
| PDF | TCPDF / FPDI, miniatures via Poppler (`pdftoppm`) |
| Front | Tailwind 3, DaisyUI 4.12.2 (thème `cidst`, `darkTheme: false`, **administration seulement**), `@tailwindcss/forms`, Alpine, Chart.js, Vite 8, Node 22 |
| Tests / qualité | Pest 4, Pint (preset laravel), hooks Husky, GitHub Actions |

Extensions PHP requises en plus de celles de Laravel : `pdo_mysql`, `gd`.

Le site **public** n'utilise pas DaisyUI : il lit les variables `--color-primary-rgb` et `--color-secondary-rgb` injectées à l'exécution.

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

Renseigner la base de données dans `.env` avant `php artisan migrate`. En développement, remplacer `npm run build` par `npm run dev`. Après chaque récupération de code : `php artisan migrate` (une migration oubliée casse l'enregistrement des réglages).

Points d'attention :

- `APP_URL` local : `http://laravel-academy.test` (Laragon).
- Poppler doit être installé pour les miniatures PDF (`PDF_THUMBNAIL_POPPLER_BINARY` pour un chemin personnalisé, voir `.env.example`). Sans lui, l'échec est silencieux : seul `storage/logs/laravel.log` le signale. Repli : `NullPdfThumbnailGenerator`.
- File d'attente : `QUEUE_CONNECTION=database` par défaut. Sans worker, les miniatures et variantes d'images ne sont jamais générées (voir [Exploitation](#9-exploitation)).
- Après la migration : `php artisan media:backfill-dimensions` renseigne les dimensions des images existantes (idempotente, `--dry-run` pour simuler).
- Contrôle du serveur : `php artisan app:check-environment`.

Comptes de test locaux : `admin@academy.local` (Super Admin) et `user@academy.local` (sans rôle, pour tester le 403). Les mots de passe se définissent localement et ne sont jamais versionnés.

## 4. Architecture et découpage

### Structure de `app/`

| Dossier | Contenu |
|---|---|
| `Blocks/` | `BlockRegistry` + `Rules/` (une classe de règles par type de bloc) |
| `Contracts/` | `BlockRules`, `DeclaresMediaFields`, `HasPublicUrl` (implémenté par `Article` et `Page` ; `PdfDocument` n'a que `publicUrl()`) |
| `Enums/` | `UserRole` : noms des rôles Spatie |
| `Http/Controllers/` | Publics (Home, Article, Page, PdfDocument, Slider, Search, Contact, Sitemap, Robots), `Auth/`, `ProfileController`, `Admin/` (un contrôleur par module) |
| `Http/Middleware/` | `CheckRole`, `ConfirmDeleteCode`, `ForcePasswordChange`, `SecurityHeaders`, `TrackPageView` |
| `Http/Requests/` | `FormRequest` + `Concerns/` (`ValidatesPageFields`, `ValidatesArticleMedia`, `ValidatesSeoFields`) |
| `Jobs/` | `ProcessMediaImage` |
| `Models/` | `User`, `Article`, `Page`, `PageBlock`, `Media`, `PdfDocument`, `SiteSetting`, `SeoMeta`, `PageView`... |
| `Observers/` | `PublicLayoutCacheObserver`, `PublicPageCacheObserver`, `SitemapCacheObserver` (branchés dans `AppServiceProvider`) |
| `Services/` | `PageBuilder/` (`BlockSyncService`...), `Dashboard/`, `Theme/`, `Environment/`, `PdfThumbnail/`, `MediaUploader`, `MediaOverviewService`, `SeoService`, `SitemapUrlBuilder` |
| `Support/` | `PublicPageCache`, `PublicLayout`, `SqlDate`, `VideoEmbed` |

### Routes (`routes/web.php`)

Trois niveaux : public ; authentifié + rôle Super Admin **ou** Publication (articles, pages, blocs) ; authentifié + Super Admin seul (menus, catégories, PDF, réglages, messages, utilisateurs). Les alias de middleware sont dans `bootstrap/app.php` (`role`, `force.password.change`, `track.view`, `confirm.delete`).

### Parties existantes

| Partie | Rôle |
|---|---|
| `Services\Dashboard\DashboardPeriod` | Période du tableau de bord : `week`, `month` (défaut), `year`, `all` (plafonné à 60 mois). `options(withGlobal: false)` retire Global (page Audience). Variation `null` si la période précédente vaut 0. |
| `Services\Theme\ColorConverter` | Convertit `#RRGGBB` en triplet RGB (Tailwind) et en OKLCH (DaisyUI 4), avec la couleur de contraste. |
| `Services\Theme\ThemeStyleBuilder` | Produit le bloc CSS du thème actif (voir [Thèmes](#thèmes)). |
| `MediaOverviewService` | Vue d'ensemble de la médiathèque (compteurs, alertes, derniers ajouts) et règle `isInUse(Media)`. |
| `MediaSyncService` | Synchronisation des couvertures et images liées aux contenus. |
| Traits `HasOrphanMediaCleanup`, `HasOrderedMediaCollection` | Nettoyage des médias orphelins, collections de médias ordonnées. |
| `Middleware\SecurityHeaders` | En-têtes de sécurité HTTP, appliqué au groupe `web` dans `bootstrap/app.php`. |
| Middleware `track.view` | Suivi des vues (aujourd'hui limité à `blog.show`). |

### Pages composées de blocs

- Une page est composée de `PageBlock` ordonnés et imbriquables (colonnes, accordéon). Chaque type de bloc a une classe de règles (`app/Blocks/Rules`), une entrée dans `BlockRegistry`, une entrée dans `BlockFieldCatalog` (si éditable), un partial admin et un partial public.
- `BlockSyncService::sync` : verrou de ligne, contrôle de version (`StaleBuilderException`), validation de tout le plan avant écriture, transaction, puis `PublicPageCache::forgetAll()` après commit. Atomicité prouvée par 5 tests (`BlockSyncRollbackTest`).
- Un type absent de `BlockFieldCatalog` n'est jamais modifié ni supprimé par `sync` (seul l'ordre change).
- Le traitement des fichiers de blocs est centralisé dans `BlockMediaService`. La lecture des dimensions d'image passe par `MediaDimensionsReader`.
- Chargement des images publiques : règles imposées par `tests/Feature/PublicImgLoadingTest.php`.

### Règle « média utilisé »

Un média est utilisé si l'un de ces quatre cas est vrai (quatre `exists()` portables dans `MediaOverviewService::isInUse`) : présent dans le pivot `mediables`, `articles.cover_media_id`, `articles.image = media.path`, `seo_metas.og_image = media.path`. Cette règle protège toute suppression de fichier.

Limite connue : le format de `og_image` (chemin ou URL) n'est pas vérifié. Une URL donnerait un faux négatif.

### Faits à connaître sur les médias

- L'événement `deleting` de `Media` supprime fichier, miniature et variantes. `Media::query()->delete()` groupé ne le déclenche pas : boucler sur les modèles.
- `Media::usages()` fait un N+1 : ne pas l'utiliser en liste.
- Filtrer sur `mime_type`, jamais sur `type` (enum).
- `mediables` est en cascade sur `media_id` ; `articles.cover_media_id` est en `nullOnDelete`.
- Les sliders passent par le pivot et par `sliders.image = media.path`.
- Le disque public a pour URL `APP_URL/storage` ; le logo est servi par `asset('storage/' . logo_path)`.
- `SiteSetting::current()` est mémorisé dans le conteneur et en cache permanent, invalidé à la sauvegarde.

### Thèmes

- Registre : `config/themes.php` (clés `default` et `themes`). Thèmes livrés : `cidst` (défaut, aucune palette imposée) et `itadori`.
- Colonne `site_settings.theme` : `string(30)`, `DEFAULT 'cidst'`, `NOT NULL`. Choix dans l'administration : **Réglages, bloc « Thème du site »**.
- `<x-theme-style />` (layouts public et admin, après `@vite`) transmet `getRawOriginal('theme')` à `ThemeStyleBuilder`, qui génère `html:root{--p;--pc;--a;--ac;--s;--sc;--n;--nc;--color-primary-rgb;--color-secondary-rgb}`. Un thème inconnu en base retombe sur `cidst`.
- **Règle** : les couleurs saisies dans les réglages (`primary_color`, `secondary_color`) ne s'appliquent qu'au thème `cidst`. Tout autre thème impose sa palette (primary, secondary, accent).
- **Contraste** : chaque palette fixe ses propres couleurs de texte (`*_content`), car le calcul automatique de DaisyUI peut passer sous 4,5:1. Un test échoue si une paire texte/fond passe sous 4,5.
- **Ajouter un thème** : ajouter une entrée dans `config/themes.php` (palette complète avec `primary`, `secondary`, `accent` et leurs `*_content`). Le sélecteur, la validation et le test de contraste le prennent en compte.
- Le mode sombre d'Itadori est écarté (contrastes insuffisants).
- Ne pas utiliser les accesseurs `primary_color` / `secondary_color` du modèle pour générer le thème (ils renvoient `#000000` / `#FFFFFF` si la valeur est invalide, comportement figé par `SiteSettingColorTest`) : le builder lit les valeurs brutes.
- Défauts en base : `#E80000` / `#1A1A1A`, différents de ceux du thème DaisyUI (`#C41E2A` / `#17181A`). Décision du propriétaire en attente.
- Fond, surfaces, bordures, muted et arrondi restent des hexadécimaux figés dans les configs Tailwind (public et admin) : chantier prévu (voir feuille de route).
- Règle de chaque lot design : mobile-first, paliers Tailwind par défaut, vérification à 360, 768, 1024, 1440 et 1920 px.

### SEO

- `config/seo.php` : textes SEO des pages sans modèle (`home`, `blog`, `blog_category`, `pages`, `documents`, `documents_category`, `sliders`, `contact`, `search` ; `title`, `description` de 160 caractères maximum, `noindex` facultatif) et libellés du fil d'Ariane. Jetons `:site` et `:name`. Les textes sont en config et non dans `lang/` (la locale par défaut est `en` ; `lang/fr` ne contient que `validation.php`).
- `SeoService` (statique) : `title`, `description`, `image`, `canonical`, `noIndex`, `ogType` (modèle) ; `forPage()` (page sans modèle) ; `listCanonical()` ; `logoUrl()` ; `structuredData()` (blocs JSON-LD) ; `jsonLd()` (encodage avec `JSON_HEX_TAG` et `JSON_HEX_AMP`, anti-injection).
- Layout : `resources/views/components/layout.blade.php` (props `seo`, `seo-page`, `seo-replace`) inclut `partials/seo-meta.blade.php`, qui inclut `partials/seo-jsonld.blade.php`.
- Table `seo_metas` (`meta_title`, `meta_description`, `og_image`, `canonical_url`, `no_index`), écrite par le trait `SavesSeoMeta`. Formulaire admin : `admin/partials/seo-fields.blade.php`. Validation (`ValidatesSeoFields`) : `meta_title` 60 max, `meta_description` 320 max, `canonical_url` `url:http,https` 255 max, `no_index` booléen.
- Comportements : recherche en `noindex` ; canonical des listes avec `?page=N` dès la page 2 ; image sociale par défaut = logo des réglages ; JSON-LD `Organization` et `WebSite` sur l'accueil, `NewsArticle` ou `WebPage` plus `BreadcrumbList` sur articles et pages ; pas de `SearchAction`.
- **Ajouter une liste avec SEO** : clé dans `config/seo.php`, prop `seo-page` sur `x-layout`, ligne dans le dataset de `tests/Feature/SeoListingPagesTest.php`.
- Sitemap : `SitemapController` (cache 1 h, clé `sitemap.xml`) délègue à `SitemapUrlBuilder` : accueil, pages de liste non vides (`/blog`, `/pages`, `/documents`), articles, pages (hors `noindex`), documents PDF. `SitemapCacheObserver` est branché sur `Page`, `Article`, `SeoMeta` et `PdfDocument`. Les pages de catégorie ne sont pas dans le sitemap.

### Commandes Artisan du projet

| Commande | Rôle |
|---|---|
| `media:prune` | Supprime les médias liés à aucun contenu |
| `media:backfill-dimensions [--dry-run]` | Renseigne `width` et `height` des images existantes (idempotente) |
| `media:backfill-variants` | Génère les variantes d'images manquantes |
| `articles:migrate-to-media [--dry-run]` | Migre les images d'articles vers la médiathèque |
| `sliders:migrate-to-media [--dry-run]` | Migre les images de sliders vers la médiathèque |
| `app:migrate-videos-to-media [--dry-run]` | Migre les vidéos vers la médiathèque |
| `blocks:audit-phantom-columns` | Audit des blocs colonnes (voir `AuditPhantomColumnBlocks`) |
| `app:check-environment` | Vérifie l'environnement du serveur |
| `page-views:prune` | Purge les anciennes vues (planifiée chaque semaine) |

## 5. Conventions de développement

- **Code** : un trait ou service par responsabilité. Les validations vont dans des `FormRequest` dédiés (jamais `$request->validate()` inline pour un nouveau module), avec `exists:table,id` pour toute liste d'identifiants (`sync`, `attach`).
- **Rôles** : uniquement via `App\Enums\UserRole`. La table des rôles est `spatie_roles` : ne jamais écrire `exists:roles,name`, utiliser `Rule::exists((new Role)->getTable(), 'name')`.
- **Modèles** : `$fillable` strict ; `user_id` toujours assigné côté serveur.
- **Slugs et visibilité** : un seul générateur de slug (trait `HasSlug`) et un seul scope `publiclyVisible` (`is_published`, `published_at` non nul et `<= now()`) pour tout contenu publiable. `NULL <= now()` est toujours faux : un `published_at` nul rend le contenu invisible sans erreur. `[à vérifier : traits créés ou non]`
- **Fichiers** : UTF-8 sans BOM, LF (préserver CRLF sur un fichier existant, par exemple `sidebar.blade.php`).
- **Git** : une branche par sujet depuis `master` (`feat/`, `fix/`, `perf/`, `chore/`, `ci/`, `docs/`), fusionnée en `--no-ff --no-edit`. Commits ASCII sans accents, messages sémantiques (`feat:`, `fix:`, `refactor:`, `test:`, `chore:`, `ci:`, `docs:`, `style:`). Ne jamais mélanger sécurité et fonctionnalité dans un commit. Ne pas réécrire l'historique de `master`. Après le merge : `git push`, `git log origin/master --oneline -3`, `git branch -d`.
- **Avant de commiter** : `vendor/bin/pint --dirty`, puis `git add`, puis `git status --short`. Le hook pre-push contrôle l'arbre de travail et non le commit : un fichier modifié mais non ajouté peut faire passer le hook tout en laissant un commit incorrect.
- **Tests** : test rouge d'abord, sur échec d'assertion (jamais sur une erreur PHP ; utiliser l'opérateur null-safe si un objet peut être absent), puis correctif minimal, puis vert, puis mutation de contrôle (casser une ligne, vérifier le rouge, restaurer, `git status` vide).
- **Requêtes d'écriture dans les tests** : ajouter `assertRedirect(...)` ou `assertOk()`. `assertSessionHasNoErrors()` seul ne détecte pas une erreur 500, car une 500 n'a pas d'erreurs de validation.
- **Helpers Pest globaux** : `adminUser()`, `publicationUser()` et `setDeleteCode()` existent dans `tests/Pest.php`. Ne pas créer d'autre fonction globale : utiliser des closures dans `beforeEach` (par exemple `$this->useTheme = function ...`).
- **Tests créant des `Media`** : toujours `Queue::fake()` ou `Bus::fake()`.
- **Front** : CSS et JS non couverts par Pest : `npm run build` puis vérification visuelle (Ctrl+F5).
- **Ne pas migrer le hero public** (`BlockHeroMediaImgTest`).

## 6. Environnement local Windows / Laragon

### PHP

Un seul PHP doit être actif : celui de Laragon (`C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\`). L'ancien `C:\Dev\Tools\php\` a été retiré du PATH : ne pas le réinstaller. Vérifier avec `where.exe php` (pas `where`, qui est un alias PowerShell). Ce double-PHP était un accident historique, à ne jamais reproduire en production.

### Terminaux VS Code

`.vscode/settings.json` est versionné (forcé avec `git add -f`) et ouvre le terminal dans le dossier du projet. Toujours deux terminaux : « Serveur » (`php artisan serve`, rien d'autre dedans) et « Commandes » (git, artisan, npm).

Le terminal Laragon est Bash/MinGW (`grep`, slashs `/`), alors que celui de VS Code est PowerShell 5.

### Pièges connus

| Piège | Remède |
|---|---|
| Commandes collées ensemble par le terminal (`--shortvendor/bin/pint`) : elles ne s'exécutent pas, ou s'exécutent tronquées | Coller **une** commande à la fois et attendre sa fin |
| `git checkout -b` échoué (branche déjà existante) : la suite part sur la mauvaise branche | Toujours `git branch --show-current` avant `git am`, avant les mutations et avant le merge |
| `git am` lancé sur une branche inattendue : `.git/rebase-apply` reste | `git am --quit` (ne touche ni HEAD ni les fichiers) |
| `git checkout -- <fichier>` dans une boucle de mutations annule aussi un correctif non commité | Commiter le correctif **avant** les mutations |
| `notepad` ne bloque pas PowerShell : `git diff` lancé juste après affiche un diff vide | Enregistrer d'abord, puis lancer les commandes |
| `composer dump-autoload` silencieux : lancé depuis `C:\laragon\www` au lieu du dossier du projet | `cd C:\laragon\www\laravel-academy`, puis `composer dump-autoload -o` |
| Git « dubious ownership » dans le terminal Laragon | `git config --global --add safe.directory C:/laragon/www/laravel-academy` (une seule fois) |
| `migrate:rollback --step=N` annule par ordre chronologique, pas par nom | Annuler un cran à la fois, `migrate:status` après chaque étape |
| `sf_proc_00.out.lock` (Permission denied) : `artisan test` plante, les miniatures PDF échouent sans erreur | `$env:TEMP = "$PWD\storage\framework\tmp"; $env:TMP = $env:TEMP; New-Item -ItemType Directory -Force $env:TEMP \| Out-Null` puis `./vendor/bin/pest`. Valable pour la session du terminal. |
| Accents déformés à l'affichage (« Ã© ») : faux problème d'encodage, le fichier est correct | Lire avec `Get-Content -Encoding UTF8`, ou `chcp 65001` dans cmd.exe |
| `findstr` et `type` peu fiables pour juger l'encodage d'un fichier | `Select-String` ou une vérification hex via Tinker |
| PowerShell 5 : `Select-String` n'a pas `-Recurse` ; `@(@("a","b"))` aplatit ; pas de `String.Replace` à 3 arguments | `Get-ChildItem -Recurse \| Select-String ...`, toujours `-Encoding UTF8` |
| Verrous de fichiers Windows (antivirus, VPN, indexation) sur `storage/framework/testing` ; suite lente (150 à 400 s selon la charge) | Fermer les processus concernés ; exclure `vendor` de l'antivirus |
| `Vite manifest not found` apparu une fois en local, transitoire | Côté tests, `withoutVite()` dans `tests/TestCase.php` |

Règle générale : une confirmation verbale ne vaut pas sauvegarde disque. Revérifier un fichier par lecture indépendante avant de le considérer terminé.

En cas d'erreur serveur inexpliquée, lire `storage/logs/laravel.log` (heure UTC = heure locale moins 3 h).

## 7. Tests et intégration continue

- **Local** : `./vendor/bin/pest --compact`. **936 tests (2496 assertions)** à `4d1da21`, sur SQLite en mémoire.
- **Hooks Husky** : pre-commit `php -l` ; pre-push `pint --test` puis `pest --compact` complet (2,5 à 7 minutes). Le push est bloqué sur toute erreur de style ou de test.
- **GitHub Actions** (`.github/workflows/tests.yml`), sur push et pull_request :
  - `tests` : matrice PHP 8.3 / 8.4 / 8.5 (`fail-fast` désactivé), Poppler installé, Pint sur 8.3 seulement, Pest sur SQLite ;
  - `tests-mysql` : service MySQL 8.4, PHP 8.3, Pest avec `DB_CONNECTION=mysql` (vert à `3f031c4`) ;
  - `frontend` : Node 22, `npm ci`, `npm run build`, tout avertissement Vite fait échouer le job.
- Un échec isolé de l'étape `apt-get` (code 100) a déjà été observé sur un job : « Re-run failed jobs » suffit, mais une nouvelle tentative automatique dans le workflow reste à ajouter.
- Minimum attendu par module dans `tests/Feature` : visibilité publique, validation des entrées sensibles, suppression en cascade des fichiers.
- À ajouter en CI : `composer audit --abandoned=report`, Larastan (niveau ≥ 5), tests de fumée navigateur sur le builder.

## 8. Sécurité

Déjà en place :

- En-têtes HTTP (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy) via `SecurityHeaders`.
- Limitation des tentatives de connexion (5 par e-mail + IP, natif Breeze).
- Politique de mot de passe renforcée dans `AppServiceProvider`, active automatiquement en production.
- `$fillable` strict sur les modèles principaux ; validation des catégories (`exists:categories,id`) dans `ArticleController`.
- **Dernier Super Admin protégé** (`User::isLastSuperAdmin()`) ; **compte auteur de contenu non supprimable** (`User::ownsContent()`) ; un Super Admin ne supprime pas son propre compte.
- **`confirm.delete`** (`ConfirmDeleteCode`) : le rôle Publication doit fournir le champ `delete_code` pour supprimer un article ou une page ; le Super Admin en est exempté. Le code est haché dans `SiteSetting.delete_code_hash` et se définit dans les réglages, **8 caractères minimum** (les codes plus anciens restent valides).
- **Limitation des tentatives du code de suppression** : clé `confirm-delete:<id utilisateur>` (repli sur l'IP), 5 échecs en 900 secondes, puis refus même avec le bon code ; un code correct remet le compteur à zéro ; refus par redirection avec erreur sur `delete_code` (pas de 429) ; un envoi sans code compte comme un échec ; compteur en cache (base) en production.
- **JSON-LD** : encodage anti-injection (`JSON_HEX_TAG`, `JSON_HEX_AMP`) dans `SeoService::jsonLd()`.

À prévoir :

- Étendre ou non `confirm.delete` aux sliders, bandeaux et blocs (décision du propriétaire).
- 2FA pour les comptes Super Admin.
- Scan de dépendances automatisé (`composer audit` en CI, Dependabot ou Renovate).
- Procédure documentée de rotation d'`APP_KEY`.
- Politique de suppression compatible RGPD (export et suppression sur demande) si déploiement en Europe ou en administration.
- Content-Security-Policy par groupe de routes (public d'abord).
- Cascade en base : `articles.user_id` et `pages.user_id` en `cascadeOnDelete` (protection seulement applicative via `ownsContent`).
- Paquet abandonné connu : `setasign/fpdi-tcpdf`, sans remplaçant suggéré (backlog). `composer audit` renvoie donc le code 1 : utiliser `--abandoned=report`.

## 9. Exploitation

- **Planification** : `page-views:prune` (chaque semaine) et `audience:prune` (chaque jour, conserve 13 mois de détail) sont planifiées dans `routes/console.php`. Sans le cron chaque minute `php artisan schedule:run`, aucune des deux ne tourne.
- **File d'attente** : sans worker permanent, utiliser `queue:work` supervisé, ou `queue:work --stop-when-empty` lancé par cron. Sans cela, miniatures et variantes d'images ne sont jamais générées.
- **Choix d'hébergement** (mutualisé ou VPS) : non tranché. Il décide du worker et du cron.
- **Base de données** : MySQL 8.4 LTS ou MariaDB en production (disponible sur tout hébergement mutualisé). SQLite n'est utilisé qu'en mémoire pour les tests ; la CI contrôle en plus la suite sur MySQL 8.4.
- **Vérification** : `php artisan app:check-environment`.

## 10. Déploiement

> Ne pas ouvrir le site au public tant que cette liste n'est pas cochée. Rien n'est déployé avant la fin du projet ; ne pas ouvrir `chore/deploy-shared`.

### Critique : sécurité

- [ ] `APP_DEBUG=false`
- [ ] HTTPS actif (Let's Encrypt), `APP_URL=https://…`, redirection HTTP vers HTTPS
- [ ] `APP_KEY` propre à la production (`php artisan key:generate`), jamais celle du développement
- [ ] Mots de passe forts pour tous les comptes existants (`admin@academy.local` et `user@academy.local` ont des mots de passe simples, posés via Tinker)
- [ ] `composer audit --abandoned=report` sans avis de sécurité juste avant la mise en ligne (dernier contrôle : 08/10/2026, aucun avis)
- [ ] `.env` absent de Git ; `.env.example` sans valeur sensible
- [ ] Vérifier en production que l'ancienne table `roles` n'existe plus (`SHOW TABLES LIKE 'roles';`, supprimée par la migration `2026_07_21_114804_drop_roles_table`)

### Important : serveur

- [ ] Base de données de production configurée (MySQL 8.4 ou MariaDB)
- [ ] PHP ≥ 8.3, puis `composer check-platform-reqs`. Validé en local sur 8.3.30 ; la CI couvre 8.3, 8.4 et 8.5
- [ ] Extension `gd` ; binaire `pdftoppm` (paquet `poppler-utils`)
- [ ] Worker de file supervisé (Supervisor ou systemd) : `php artisan queue:work`, et `php artisan queue:restart` à chaque déploiement ; cron `schedule:run` chaque minute
- [ ] `php artisan migrate --force`, puis `php artisan media:backfill-dimensions`
- [ ] `php artisan storage:link` au premier déploiement (le disque `public` en dépend pour toutes les images). Si l'hébergeur interdit les liens symboliques, prévoir une alternative (voir `docs/REQUIREMENTS.md`)
- [ ] SMTP réel (`MAIL_MAILER`, `MAIL_HOST`, adresse expéditrice valide) ; tester `/contact`, `/admin/messages` et l'alerte e-mail. L'alerte est envoyée pendant la requête (non mise en file) : un SMTP lent fait attendre le visiteur, et un échec est seulement loggé
- [ ] `trustProxies` (commenté dans `bootstrap/app.php`) si un proxy ou un CDN se place devant le site, sinon les visiteurs sont sous-comptés
- [ ] Fuseau : `APP_TIMEZONE` vaut `Indian/Antananarivo` par défaut (UTC+3, sans heure d'été) ; vérifier qu'il n'est pas écrasé dans le `.env` de production. Ne plus le changer après la mise en ligne : les dates stockées sont enregistrées sans fuseau et seraient décalées
- [ ] `CSP_MODE` : `report-only` par défaut (`config/security.php`). Relire la console du navigateur, puis passer à `enforce`
- [ ] Après chaque déploiement : `config:clear` et `cache:clear` (sinon `__PHP_Incomplete_Class` sur les modèles mis en cache), **puis** `config:cache`, `route:cache`, `view:cache`. Contrôle fait le 9 novembre 2026 : les trois compilent sans erreur sur `master` (aucune closure de route, aucune erreur Blade)
- [ ] Permissions de `storage/` et `bootstrap/cache/`
- [ ] `npm run build` (pas de `npm run dev`)
- [ ] Sauvegardes automatisées de la base **et** des fichiers (`storage/app/public`, médiathèque), idéalement chiffrées
- [ ] `LOG_LEVEL` adapté, avec rotation des logs

### À considérer

- [ ] `expose_php = Off` dans `php.ini` (masque `X-Powered-By: PHP/8.3.30`)
- [ ] Content-Security-Policy
- [ ] Endpoint de santé (`/up`, natif Laravel) pour la supervision externe

## 11. Feuille de route

### Prochaines étapes (ordre proposé)

1. **CI** : nouvelle tentative automatique sur `apt-get`, `composer audit`, tests de fumée navigateur sur le builder. Larastan tourne en CI et dans le hook `pre-push` (Pint, Larastan, Pest) depuis le 9 novembre 2026.
2. **Boutons** : composant Blade `<x-button>` inspiré de shadcn/ui, adapté à Tailwind 3 (ne pas lancer `npx shadcn`, le projet n'est pas en React ni en Tailwind 4). Variantes `default`, `outline`, `secondary`, `ghost`, `destructive`, `link` ; tailles `xs`, `sm`, `default`, `lg`, `icon` ; un lien est une vraie balise `<a>` portant les mêmes classes, jamais `role="button"`.
3. **Thèmes, surfaces** : fond, surfaces, bordures, muted et arrondi deviennent des variables (dans les configs Tailwind, seules les définitions changent, pas les usages dans les vues). Contour des champs de formulaire à au moins 3:1.
4. **SEO restant** : relire `config/seo.php` ; valider les JSON-LD (Rich Results Test) et mesurer Lighthouse ; champ `og_image` dans l'administration avec le sélecteur de médias existant (`MediaPicker`, mode `single`) et contrôle d'existence du média ; SEO de `documents.show` ; le titre de `/documents` ne suit pas `pdf_documents_title` ; pages de catégorie dans le sitemap (observers sur `Category` et `PdfCategory`) ; fil d'Ariane d'un article sans sa catégorie ; textes SEO éditables (colonne JSON, repli sur `config/seo.php`).
5. **Nettoyage** : `Http/Controllers/ArticleController` (public) utilise `SavesSeoMeta` sans enregistrer de SEO (code mort, commit `chore:` séparé).
6. **`confirm.delete`** : décider de l'extension aux sliders, bandeaux et blocs, puis l'implémenter test d'abord.
7. **Page Builder** : `BlockDuplicator` côté serveur (annuler, dupliquer, copier-coller) **avant** toute interface, puis fusion des deux éditeurs de blocs. Chantier le plus risqué, à ne lancer qu'avec une CI solide.
8. **Tableau de bord** : thème personnalisable (variables CSS depuis `SiteSetting`, composants Blade, mode sombre), sidebar (icônes, groupes, `aria-current`), restes `dark:` de Breeze (indigo, vert, rouge) ; les `gray-*` sont migrés (garde : NoHardcodedGrayTest).
9. Suppression des médias inutilisés (Super Admin, refus si `isInUse`, boucle sur les modèles).
10. Suivi des visites : filtre des robots (test d'abord), puis accueil et pages. Seule `blog.show` porte `track.view:article`, donc les chiffres sont incomplets et possiblement gonflés.
11. Années civiles `?year=2025` (décision en attente ; recommandation : année en cours jusqu'au mois courant, logique dans `DashboardPeriod`). Le lot `AudienceController` / `DashboardPeriod` est fait : périodes en jours entiers, sans Global, minuit heure de Madagascar.
12. Polices auto-hébergées, rapport CSV.
13. Déploiement, en dernier.

### Dette connue

- `PageController` admin gère encore `image` alors que `pages.image` est supprimée par migration `[non vérifié]`.
- `resources/views/dashboard.blade.php` probablement orphelin (la route `/dashboard` redirige) `[non vérifié]`.
- La transaction de `MediaSyncService` / `ArticleController` ne protège pas le disque (fichier supprimé avant un échec possible).
- `layout.blade.php` : tiroir mobile en JS inline, sans Échap, `aria-expanded` ni focus piégé ; pas de `max-w` sur `<main>`.
- Safelist héritée de Breeze dans `tailwind.config.js` à auditer.
- Graphiques : `<canvas height="90">` sans conteneur de hauteur fixe, d'où le graphique écrasé en `?period=all`.
- `admin/media/index.blade.php` : N+1, `asset('storage/'.$path)` cassé pour vidéos externes et PDF.
- Sous-menus (dropdown) du module Menus : `parent_id` nullable + logique récursive.
- Non vérifiés : Alpine, builder, CSP, tests navigateur.

### Évolutions futures (ne pas démarrer maintenant)

| Ajout | Phase | Justification | Statut |
|---|---|---|---|
| `spatie/laravel-medialibrary` (à évaluer face à la solution maison) | 2 | Conversions, compteur de références, disques multiples déjà gérés | Non démarré |
| Traits `HasSlug`, `HasPublicVisibility` | 1 | Supprime la duplication observée entre modules | À vérifier |
| 2FA Super Admin | 4 | Standard attendu en contexte institutionnel | Non démarré |
| Accessibilité WCAG AA | 3 | Souvent obligatoire en marchés publics / universités | Non démarré |
| i18n / multi-langue (colonne `locale`) | 1 | Coûteux à ajouter après coup | Non démarré |
| Health-check | 4 | Supervision externe | Natif `/up` |
| Logs structurés JSON (mode Enterprise) | 4/5 | Ingestion par un outil de supervision | Non démarré |
| Chiffrement des sauvegardes | 4 | Basique de sécurité | Non démarré |
| Docker Compose de référence (dev + prod) | 5 | Onboarding et compatibilité hébergement | Non démarré |

Architecture cible déjà actée : modes Lite / Standard / Enterprise avec détection automatique selon le serveur, outils de développement (Debugbar, Telescope) strictement séparés de la production, phases fondations, contenu, présentation, sécurité/maintenance, optimisation.