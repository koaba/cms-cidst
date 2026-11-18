# PASSATION TECHNIQUE #44 : CMS CIDST (18 novembre 2026)

Complète `docs/passation.md` (version du 16 novembre, qui garde le détail des rubriques 4.4 à 4.11 : coordonnées, pied de page, SEO, sécurité, recherche du menu, menus, réseaux sociaux). Ce document-ci donne l'état au 18 novembre et les lots postérieurs.

Convention : [non verifie] = aucune sortie reçue ne l'a confirmé. En cas de conflit entre ce document, le code et les tests, le code et les tests font foi.

## 1. Projet

CMS Laravel du CIDST (Madagascar). Site public : blog, pages à blocs, PDF, sliders, recherche, sitemap, contact. Administration à deux rôles : Super Admin et Publication (enum `App\Enums\UserRole`, spatie/laravel-permission).

Principes : mobile-first, design harmonisé site/admin, SEO soigné, code léger sans redondance, une branche par lot.

Stack : Laravel 13.42, PHP 8.3, Pest 4, Pint, Larastan, Tailwind 3, DaisyUI 4.12, Alpine, Chart.js, Vite 8.

Dépôt : github.com/koaba/cms-cidst (branche principale : master).

Poste du client : Windows, Laragon, PowerShell 5, dossier `C:\laragon\www\laravel-academy`, MySQL local (laravel_academy), tests en SQLite mémoire.

Hooks Husky : pre-commit = lint PHP des fichiers modifiés. pre-push = Pint, Larastan, puis Pest complet (4 processus, 350 à 460 s).

## 2. État git au 18/11/2026

master = origin/master = aa89bb3 (fusion de `test/contact-alert-contenu`). Dernier Pest complet : 1730 tests, 5089 assertions, tous verts. Pint et Larastan sans erreur.

Tags stables (posés après le push de master) :

| Tag | Commit |
|---|---|
| stable-2026-11-18-lot3f1b | 50c52a3 |
| stable-2026-11-18-favicon | a25ac56 |
| stable-2026-11-18-envprod | f3049ae (replacé par script, sortie non vue : vérifier avec `git rev-parse --short`) |
| stable-2026-11-18-contactalert | aa89bb3 |

Les branches `feat/favicon-logo`, `feat/env-production-example` et `test/contact-alert-contenu` sont fusionnées et peuvent être supprimées.

## 3. Méthode de travail

- Test rouge d'abord (sur une assertion, jamais sur une erreur de syntaxe), implémentation, mutation de contrôle d'une ligne (le rouge est attendu), restauration (vert), commit.
- `git add` fichier par fichier, jamais `git add .` ; fusions en merge commit (`merge --no-ff`), jamais en squash.
- Scripts d'édition : ancre comptée (refus si le nombre d'occurrences diffère de 1), idempotents, UTF-8 sans BOM, détection CRLF/LF, mutation dans un `try/finally` qui restaure le fichier.
- Accents dans les fichiers PHP : séquences `\u{e9}` ou entités HTML (PowerShell 5 abîme l'affichage).
- Avant le Pest complet : `$env:TEMP = "$PWD\storage\framework\tmp"; $env:TMP = $env:TEMP; New-Item -ItemType Directory -Force $env:TEMP | Out-Null`, puis `php artisan view:clear`.
- Helpers Pest dans `tests/Pest.php` : `adminUser()`, `publicationUser()`, `setDeleteCode()`. Ne jamais nommer un helper `visit()` (nom réservé). `contactPayload()` est défini dans `tests/Feature/ContactMessageTest.php` : ne pas le redéclarer.

Pièges vécus (3 accidents le 18/11) :
- Deux commandes collées sans saut de ligne se fondent en une. Un seul bloc par collage, puis une Entrée de plus. Ne jamais coller la sortie des tests dans PowerShell.
- Ne créer un tag qu'après la ligne `master -> master` du push (le hook ne contrôle pas les tags).
- Un `git commit` oublié laisse les fichiers en M/A : push et merge répondent alors « up to date » sans rien envoyer. Vérifier `git log` et `git status` avant push.
- Les variables `$env:` de test (APP_ENV=production, etc.) sont à retirer de la session après usage.
- Format qui évite les erreurs : une seule instruction `& { ... }` avec `if ($LASTEXITCODE -ne 0) { ...; return }` après chaque étape, une fonction de mutation, et un contrôle que chaque mutation est rouge.
- Erreur Windows « rename ... storage\framework\views ... Accès refusé (code 5) » : verrou antivirus/indexeur. Exclure `storage` dans Windows Security, `php artisan view:clear`, puis `view:cache`. Cause probable des durées de Pest très variables.

## 4. Architecture (vérifiée dans le code)

**Gabarits.** `components/layout.blade.php` (site public, inclut `partials.seo-meta` et `<x-theme-style />`), `components/admin/layout.blade.php` (administration), `layouts/app.blade.php` et `layouts/guest.blade.php` (connexion, profil). Partiels : `partials/seo-meta`, `seo-jsonld`, `favicon`.

**Favicon.** `public/favicon.ico`, 48x48, PNG dans un ICO, 721 octets, emblème du logo CIDST. `partials/favicon.blade.php` ajoute `?v=filemtime` pour forcer le rechargement. Inclus dans les 4 gabarits. Tests : `tests/Feature/FaviconTest.php` (3 tests, mutation vérifiée rouge sur /login, /admin, /profile).

**Menu admin.** `config/admin_menu.php` (clés label, route, description, active, roles). Lu par la sidebar, la section Raccourcis du tableau de bord (filtre `hasAnyRole`) et la page `/admin/guide`.

**Thèmes.** `config/themes.php` (presets cidst, bordeaux, ardoise, sable ; rôles primary, secondary, accent, bg, surface, text, muted, border ; états danger, success, warning), `ThemeRoles`, `resources/css/shared.css`, `tailwind.tokens.js`, `ThemeTokensTest`. Thèmes Clair, Sombre et de fêtes : annoncés (lots 3e et 3e-bis), non faits.

**Contact.** `ContactController` : honeypot, limite 5/min, IP hachée. L'alerte e-mail part après la réponse via `defer()` ; `alerted_at` reste vide en cas d'échec et le message est conservé. `App\Mail\ContactAlert` (méthode `build()`, sujet « Nouveau message de contact », vues `emails.contact-alert` et `emails.contact-alert-text`) est VOLONTAIREMENT sans donnée du visiteur ; destinataire = `config('mail.from.address')`. Images jointes optionnelles. Menu Messages (Super Admin) : lecture, marquage lu, bouton Répondre en mailto.

**Tableau de bord.** `DashboardTodoService` (brouillons, pages programmées, messages non lus pour le Super Admin), `DashboardPeriod`, `DashboardStatsService`.

**Code de suppression** pour Publication : middleware `confirm.delete`. Le Page Builder (`resources/js/admin/page-builder.js`) n'envoie `delete_code` que si `requiresDeleteCode && pendingDeletions > 0`.

**Page Builder.** Routes `admin/pages/{page}/builder` (show, save), vue `admin/pages/builder.blade.php`, FormRequest d'enregistrement, 42 fichiers de tests (214 tests). Le fichier `page-builder.blade.php` fourni en début de session n'a jamais été intégré.

**Audience (Super Admin).** Tables `site_visits` (détail 13 mois) et `site_visit_daily` (totaux journaliers conservés) ; middleware `TrackVisit` (GET réussis, liste blanche de routes, robots exclus, ni IP ni user-agent stockés, visiteur = hash IP+navigateur+date+APP_KEY renouvelé chaque jour) ; `AudienceService` (summary, monthly) ; page `/admin/audience`. « Visiteurs (cumul des jours) » : une personne qui revient compte chaque jour.

**Environnement.** `php artisan app:check-environment` (`CheckEnvironment`, `EnvironmentChecker`, `EnvironmentProbe`). Contrôles de production appliqués seulement si `APP_ENV=production` : APP_DEBUG (bloquant), courrier (log/array ou expéditeur example.com), APP_URL, planificateur (battement), file d'attente, lien `public/storage`, CSP.

**`.env.production.example`** (versionné, secrets vides) : APP_ENV=production, APP_DEBUG=false, APP_URL=https://votre-domaine.tld (à remplir), APP_TIMEZONE=Indian/Antananarivo, DB mysql, LOG_STACK=daily, LOG_LEVEL=warning, SESSION_DRIVER=database, SESSION_SECURE_COOKIE=true, QUEUE_CONNECTION=sync, CACHE_STORE=database, courrier smtp.gmail.com:587 avec mot de passe d'application (MAIL_USERNAME et MAIL_PASSWORD vides), CSP_MODE=enforce, PDF_THUMBNAIL_DRIVER=poppler (binaire `pdftoppm` requis sur le serveur), rétention contact 90 jours (images) et 365 jours (messages). Test : `tests/Unit/EnvProductionExampleTest.php`. Exécution à blanc le 18/11 avec ces valeurs : tous les contrôles [OK] sauf le planificateur (avertissement normal en local, pas de cron).

**Tâches planifiées** (`routes/console.php`) : `page-views:prune` (hebdomadaire), `audience:prune`, `contact:prune-images`, `contact:prune-messages` (quotidiennes), `scheduler-heartbeat` (chaque minute). Elles exigent le cron `php artisan schedule:run` chaque minute.

**Autres commandes Artisan** (rôle précis non relu) [non verifie] : AuditPhantomColumnBlocks, BackfillMediaDimensions, BackfillMediaVariants, ConvertExistingImagesToWebp, InstallWatermarkLogo, MigrateArticlesToMedia, MigrateSlidersToMedia, MigrateVideosToMedia, PruneUnusedMedia.

## 5. Tests : revue du 18/11 (analyse statique avant les derniers lots)

Aucun skip, todo ou only. Aucun test réellement sans assertion (les 8 détectés étaient des attentes Mockery ou `->throws()`).

Assertions faibles à renforcer (45 tests n'ont que `assertOk`, `assertRedirect`, `assertSessionHasNoErrors` ou `assertSuccessful` ; la plupart sont légitimes). Priorité :
- `AdminAudienceTest` « retombe sur 30 jours quand la période est invalide » : vérifier le repli réel, pas seulement le 200.
- `ConvertExistingImagesTest` (3 tests) : contrôler les fichiers convertis.
- `PageBlockSeparateurTest` et `PageBlockCitationTest` : vérifier la sauvegarde en base.
- `PublicFooterContactTest` « formats usuels de numéro ».

Trous encore ouverts :
- Logique JavaScript de l'invite du code de suppression (`page-builder.js`) : aucun test.
- Intégration du Page Builder de bout en bout (édition puis affichage public).
- Droits par rôle : auditer chaque route admin avec un test 403 pour Publication (26 tests de droits seulement).
- Audience : 27 tests, domaine sensible à la vie privée, à renforcer.
- Condition de rôle de la section Raccourcis : couverte par `AdminMenuConfigTest` (`assertDontSee` sur `/admin` pour Publication), mutation jamais faite [non verifie].
- Pest complet avec `--profile` pour isoler les tests lents et mesurer l'effet de l'exclusion antivirus.

Comblé le 18/11 : contenu de `ContactAlert` (`tests/Feature/ContactAlertContentTest.php`, 4 tests : sujet, HTML, texte, destinataire sans donnée du visiteur). Trois mutations rouges vérifiées.

## 6. Dette de code

Restante : `views_30d` encore calculé dans `DashboardStatsService` ; graphiques du tableau de bord calculés en mémoire (`groupBy`) au lieu de SQL ; la carte « Pages » compte aussi les pages programmées ; pas de redirection depuis un ancien slug ; bloc d'upload d'image dupliqué dans `Admin\PageController` ; périodes de l'Audience dupliquées par rapport à `DashboardPeriod` ; invite JavaScript du code de suppression non testée.

Résolue : titre admin fixe, classes `gray-*` en dur (0 occurrence), balise `nav` orpheline de la sidebar, contenu des e-mails de contact (18/11).

## 7. À trancher ou vérifier avant la mise en ligne

- Hébergement inconnu (cPanel, VPS ou autre) : conditionne le cron `php artisan schedule:run` chaque minute et la procédure de déploiement (`composer install --no-dev`, `php artisan migrate --force`, `config:cache`, `view:cache`). Vérifier aussi poppler (`pdftoppm`) et des limites PHP cohérentes pour les images jointes (le poste local affiche 2G, valeur Laragon).
- Envoi SMTP réel jamais éprouvé (seul `MAIL_MAILER=log` utilisé). Le mot de passe d'application Google va dans le `.env` du serveur uniquement.
- Fuseau horaire : Indian/Antananarivo (projet) ou Africa/Nairobi (client) ; même décalage.
- Couleurs de marque non tranchées : #E80000 / #1A1A1A contre #C41E2A / #17181A.
- Contrôles dans le navigateur déclarés faits par le client, résultats non vus : Audience, Messages avec compteur, Guide en Super Admin puis Publication. Favicon : contrôle visuel sur /, /login, /admin, /profile (Ctrl+F5, cache Chrome) non vu [non verifie].

## 8. Ordre recommandé pour la suite

1. Vérifier le tag envprod (`git rev-parse --short stable-2026-11-18-envprod` doit donner f3049ae).
2. Audit des routes admin et test 403 pour Publication, puis renforcement des assertions faibles listées en 5.
3. Test du Page Builder de bout en bout et du code de suppression côté JS.
4. Pest complet `--profile`.
5. Trancher l'hébergement, le SMTP, le fuseau et les couleurs, puis déployer avec la procédure de la section 7.
6. Dette de code (section 6), lot par lot.
