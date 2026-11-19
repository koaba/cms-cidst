# PASSATION TECHNIQUE #45 : CMS CIDST (19 novembre 2026)

Complete `docs/passation-44.md` (qui garde le detail des rubriques 4 a 8 du 18/11) et `docs/passation.md` (version du 16/11). Ce document donne l'etat au 19/11/2026 apres-midi et prime sur les sections 2, 6, 7 et 8 de la passation #44.

Convention : [non verifie] = aucune sortie recue ne l'a confirme. En cas de conflit entre ce document, le code et les tests, le code et les tests font foi.

## 1. Projet

CMS Laravel du CIDST (Madagascar). Site public : blog, pages a blocs, PDF, sliders, recherche, sitemap, contact. Administration a deux roles : Super Admin et Publication (enum `App\Enums\UserRole`, spatie/laravel-permission).

Principes : mobile-first, design harmonise site/admin, SEO soigne, code leger sans redondance, projet evolutif decoupe en lots (une branche par lot), options de personnalisation avancees dans le tableau de bord.

Stack : Laravel 13.42, PHP 8.3, Pest 4, Pint, Larastan, Tailwind 3, DaisyUI 4.12, Alpine, Chart.js, Vite 8.

Depot : github.com/koaba/cms-cidst (branche principale : master). Poste du client : Windows, Laragon, PowerShell 5, dossier `C:\laragon\www\laravel-academy`, MySQL local (laravel_academy), tests en SQLite memoire. Git 2.55 pour Windows.

## 2. Etat git

Etat de reference : tag `stable-2026-11-18-audiencednt` (0737cd8). Les commits posterieurs sont de la documentation uniquement. Dernier Pest complet : 1756 tests, 5194 assertions, verts (272 s, 4 processus). Pint et Larastan : sans erreur (hook de push). Tests JS : 32 verts.

Tags stables poses apres la ligne `master -> master` du push (serie `stable-2026-11-18-*`, suffixe de date garde par convention) :

| Tag | Commit |
|---|---|
| lot3f1b | 50c52a3 |
| favicon | a25ac56 |
| envprod | f3049ae |
| contactalert | aa89bb3 |
| routes403 | ecbcf57 |
| audience | 890b1a3 |
| blocstests | 2ed4d8b |
| convertwebp | 70f125f |
| footertel | ab8dd9f |
| builderflow | 64ae71e |
| jsbuilder | 2f0aa60 |
| mediareorder | 3ef0d74 |
| mediafeedback | 8124c27 |
| slugredirects | 445112c |
| dashstats | b6c5a0c |
| audiencednt | 0737cd8 |

## 3. Hooks Husky

- pre-commit : lint PHP des fichiers modifies.
- pre-push : Pint (--test), Larastan, `npm run test:js` (32 tests, environ 1 s : `contact-images.test.js`, `page-builder.test.js`, `media-reorder-lib.test.js`), puis Pest complet en parallele (4 processus, 210 a 340 s). Un push de suppression de branche ou de tag saute les verifications.
- Pas de --no-verify. Le hook ne controle pas les tags.

## 4. Architecture (verifiee dans le code)

Les rubriques de la passation #44 restent valables (gabarits, menu admin, themes, contact, tableau de bord, code de suppression, Page Builder, Audience, environnement, taches planifiees). Ajouts et precisions du 19/11 :

- **Redirection d'ancien slug (SEO).** Table `page_slug_redirects` (`page_id`, `old_slug`), modele `App\Models\PageSlugRedirect` (relation `page()`). `Page::resolveRouteBinding()` redirige en 301 vers l'adresse actuelle quand le slug demande est un ancien slug ET que la page cible est publiquement visible ; sinon 404 comme avant. Le hook `updated` de `Page` enregistre l'ancien slug, retire la redirection quand la page reprend un ancien slug, et pointe tous les anciens slugs directement vers la page (pas de chaine). Tests : `tests/Feature/PageSlugRedirectTest.php` (7 tests). Les articles n'en ont pas besoin : leur slug est genere a la creation seulement et n'est pas modifiable dans l'administration.
- **Ordre des medias.** `App\Traits\HasOrderedMediaCollection::reorderMedia(array $orderedIds)` (ids etrangers ignores, places du groupe conservees, renumerotation de 0 a n-1, transaction), appelee par `MediaOrderController::update()`. Retour d'erreur visible dans l'interface : logique testable dans `resources/js/admin/media-reorder-lib.js` (tests node), zone d'erreur rendue et masquee par defaut dans `x-admin.media-reorder-list`.
- **Tableau de bord.** `DashboardStatsService` (`app/Services/Dashboard/`) : les cartes Articles et Pages utilisent le scope `publiclyVisible()` (contenus programmes et brouillons exclus) ; `views_30d` supprime ; les graphiques sont groupes en SQL (`SqlDate::bucket` + `groupBy`, `DashboardSqlTest`). Test : `tests/Feature/DashboardCountsTest.php`.
- **Typage des relations Eloquent.** Larastan ne resout `$modele->relation` que si la methode a un type de retour natif ET un docblock generique : `/** @return BelongsTo<Page, $this> */ public function page(): BelongsTo`. Sans cela : `property.notFound` puis `method.notFound` sur le resultat.
- **Audience : respect du refus de suivi.** `TrackVisit` n'enregistre aucune visite quand la requete porte `DNT: 1` ou `Sec-GPC: 1` (methode `refusesTracking()`). Le hash visiteur est `hash_hmac('sha256', IP|navigateur|date, APP_KEY)`, renouvele chaque jour. Tests : `tests/Feature/AudienceDoNotTrackTest.php` (4 tests, 4 mutations rouges).

## 5. Methode de travail (a respecter)

- Test rouge d'abord, sur une assertion (jamais sur une erreur de syntaxe), implementation, mutation de controle d'une ligne (rouge attendu), restauration (vert), commit.
- Une mutation doit etre sensible : si le test reste vert, l'assertion est trop grossiere.
- `git add` fichier par fichier, jamais `git add .` ; fusions --no-ff, jamais en squash ; une branche par lot ; tag seulement apres `master -> master` (le script ne tague que si le push a reussi et que master egale origin/master).
- Scripts PowerShell 5 : une seule instruction `& { ... }` collee en un bloc, `$ErrorActionPreference = 'Stop'`, controles de depart (branche, master sur le bon commit, master egal a origin/master, arbre propre, branche inexistante), edition par ancre comptee (refus si le nombre d'occurrences est different de 1), idempotente, UTF-8 sans BOM, detection CRLF/LF, mutation dans un `try/finally` qui restaure les octets d'origine. Pour lancer un test sous mutation sans afficher sa sortie : passer `$ErrorActionPreference` a `Continue` autour de `php artisan test ... *> $null`, puis le remettre a `Stop`.
- Tests JS : `node --test` (aucune dependance), via `npm run test:js`. Ne jamais lire la sortie de node dans le script : tester seulement `$LASTEXITCODE`, sans pipe ni capture.
- Helpers Pest dans `tests/Pest.php` (`adminUser()`, `publicationUser()`, `setDeleteCode()`). Les fonctions declarees dans un fichier de test sont globales : prefixer (mro*, dsql*, dcnt*, psr*...) et ne jamais redeclarer builderBlock, builderSave, newRequest, pageWithTextBlock, contactPayload, e2ePublicGet, e2eTexte, publicFooterContact*, cvt*. Ne jamais nommer un helper `visit()`.
- Accents : dans les fichiers PHP, sequences `\u{e9}` ou entites HTML ; dans les scripts PowerShell, codes de caractere ou texte ASCII. Commits sans accents.
- Avant le Pest complet : TEMP et TMP vers `storage\framework\tmp`, puis `php artisan view:clear` ; restaurer ces variables apres usage.
- Commits et fusions : trailers `Co-Authored-By` et `Claude-Session` en fin de message.

## 6. Travail du 19/11 (lots clos)

- `jsbuilder` : test JavaScript du code de suppression du Page Builder.
- `mediareorder` : bogue corrige. `MediaOrderController::update()` appelait `reorderMedia()`, definie nulle part (500, rien d'enregistre, aucune erreur visible). Methode ajoutee dans le trait, 6 tests, 2 mutations rouges.
- `mediafeedback` : l'echec du glisser-deposer est maintenant affiche (message par statut : 422, 419, 403, autres, echec reseau).
- `slugredirects` : redirection 301 depuis un ancien slug de page (voir section 4).
- `dashstats` : cartes Articles et Pages sans les contenus programmes ; `views_30d` supprime.
- `audiencednt` : l'Audience respecte DNT et Sec-GPC ; hash visiteur en HMAC.
- Audit des listes ordonnees : aucun autre endpoint n'a le defaut d'ordre du Page Builder.

Correction de la passation precedente : les graphiques du tableau de bord etaient deja calcules en SQL (ils ne l'etaient plus en memoire).

## 7. Dette et trous ouverts

Corrections apres relecture du code (19/11, apres-midi) : ces points de la passation precedente sont SANS OBJET.
- Graphiques du tableau de bord en memoire : deja groupes en SQL (`SqlDate::bucket`, `DashboardSqlTest`).
- `views_30d` : supprime (lot dashstats). Carte Pages et carte Articles : corrigees (lot dashstats).
- Periodes de l'Audience dupliquees : deja mutualisees, le controleur et la vue utilisent `DashboardPeriod`.
- Bloc d'upload d'image de `Admin\PageController` : il ne reste que 2 lignes d'appel (`fromUpload` puis `attach`), la logique est dans `MediaUploader`. Refactor sans gain.
- Redirection d'ancien slug pour les articles : inutile, leur slug n'est pas modifiable.
- Branches distantes mergees : supprimees le 19/11, seule `master` subsiste (distant et local).

Verifie le 19/11, sans code a ecrire :
- Condition de role des Raccourcis : trois consommateurs de `config/admin_menu.php` portent la condition (vue du tableau de bord, sidebar, `GuideController`). Mutation de chacun en « toujours vrai » : 3 rouges, 0 trou.
- Pest `--profile` : 473 s en sequentiel, 212 a 252 s en 4 processus. Les 10 tests les plus lents ne pesent que 13 % du total (`SourceEncodingTest` 8 s, `AdminPageTitlesTest` 6,6 s, puis `ConvertExistingImagesTest`). Pas de point chaud : la duree vient de la masse (1752 tests, environ 0,27 s chacun). Aucun lot de performance a faire.

Dette reelle restante :
- Audience : DNT, Sec-GPC et HMAC faits (lot audiencednt). Reste a decider : anonymiser `visitor_hash` avant 13 mois (decision de conception ; risque faible tant que APP_KEY reste secret dans le `.env` du serveur).
- Commandes Artisan au role non relu [non verifie] : AuditPhantomColumnBlocks, BackfillMediaDimensions, BackfillMediaVariants, InstallWatermarkLogo, MigrateArticlesToMedia, MigrateSlidersToMedia, MigrateVideosToMedia, PruneUnusedMedia.
## 8. A trancher avant la mise en ligne

- Hebergement inconnu : procedure standard dans `docs/deploiement.md`. BLOCAGE : aucune commande ne cree le premier Super Admin (lot `app:create-super-admin` a faire avant la mise en ligne ; ne jamais lancer `db:seed` en production). Hebergement (cPanel, VPS ou autre) : conditionne le cron `schedule:run` chaque minute et la procedure (`composer install --no-dev`, `php artisan migrate --force`, `config:cache`, `view:cache`). Verifier poppler (`pdftoppm`) et les limites PHP des images jointes.
- Envoi SMTP reel jamais eprouve (seul `MAIL_MAILER=log` utilise) : plus gros risque. Le mot de passe d'application Google va dans le `.env` du serveur uniquement.
- Fuseau : TRANCHE le 19/11 : Indian/Antananarivo (deja la valeur par defaut du code et des modeles .env).
- Couleurs de marque : TRANCHE le 19/11 : #C41E2A / #17181A (deja dans le code ; plus proche du rouge du logo, contraste 5,89). Aucun changement de code.
- Controles dans le navigateur declares faits par le client, resultats non vus : Audience, Messages, Guide (Super Admin puis Publication), favicon sur /, /login, /admin, /profile.

## 9. Ordre recommande

1. Trancher hebergement, SMTP, fuseau et couleurs (section 8) : ce sont les seuls blocages restants, aucun n'est technique.
2. Lot « premier vrai envoi d'e-mail de contact » (SMTP reel, mot de passe d'application Google dans le `.env` du serveur uniquement).
3. Durcir la confidentialite de l'Audience ; relire le role des commandes Artisan de rattrapage.
4. Deployer avec la procedure de la section 8.
5. Apres la mise en ligne : themes Clair, Sombre et fetes (lots 3e, 3e-bis).
## 10. Pieges connus

- Coller deux commandes sans saut de ligne les fond en une : un seul bloc par collage, puis une Entree de plus. Ne jamais coller de sortie de tests ni de code d'un autre langage dans PowerShell hors d'un here-string.
- Un `git commit` oublie laisse les fichiers en M/A : verifier `git log` et `git status` avant de pousser.
- Nettoyer `storage\framework\testing` sans supprimer `.gitignore` (`-Exclude`).
- En .NET, `StartsWith(string)` est culturel : utiliser `StringComparison.Ordinal`.
- Windows « Acces refuse (code 5) » sur `storage\framework\views` : verrou antivirus ; exclure `storage` dans Windows Security.
- Panne DNS intermittente de la box (192.168.1.254) : « Could not resolve host: github.com ». Sans droits administrateur, forcer l'adresse pour Git dans la session : `GIT_CONFIG_COUNT=1`, `GIT_CONFIG_KEY_0=http.curloptResolve`, `GIT_CONFIG_VALUE_0=github.com:443:140.82.121.4` (a retirer apres usage ; l'adresse peut changer). Le hook rejoue Pest a chaque push : eviter de relancer sur une connexion instable.
- Le test EXIF de `ConvertExistingImagesTest` s'ignore si l'extension exif est absente (presente sur le poste du client).