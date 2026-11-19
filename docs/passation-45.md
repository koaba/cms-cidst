# PASSATION TECHNIQUE #45 : CMS CIDST (19 novembre 2026)

Complete `docs/passation-44.md` (qui garde le detail des rubriques 4 a 8 du 18/11) et `docs/passation.md` (version du 16/11). Ce document donne l'etat au 19/11/2026 apres-midi et prime sur les sections 2, 6, 7 et 8 de la passation #44.

Convention : [non verifie] = aucune sortie recue ne l'a confirme. En cas de conflit entre ce document, le code et les tests, le code et les tests font foi.

## 1. Projet

CMS Laravel du CIDST (Madagascar). Site public : blog, pages a blocs, PDF, sliders, recherche, sitemap, contact. Administration a deux roles : Super Admin et Publication (enum `App\Enums\UserRole`, spatie/laravel-permission).

Principes : mobile-first, design harmonise site/admin, SEO soigne, code leger sans redondance, projet evolutif decoupe en lots (une branche par lot), options de personnalisation avancees dans le tableau de bord.

Stack : Laravel 13.42, PHP 8.3, Pest 4, Pint, Larastan, Tailwind 3, DaisyUI 4.12, Alpine, Chart.js, Vite 8.

Depot : github.com/koaba/cms-cidst (branche principale : master). Poste du client : Windows, Laragon, PowerShell 5, dossier `C:\laragon\www\laravel-academy`, MySQL local (laravel_academy), tests en SQLite memoire. Git 2.55 pour Windows.

## 2. Etat git

master = origin/master = b6c5a0c (fusion --no-ff de `fix/dashboard-stats-clean`), avant le lot documentation en cours. Dernier Pest complet : 1752 tests, 5186 assertions, verts (212 s, 4 processus). Pint (567 fichiers) et Larastan : sans erreur. Tests JS : 32 verts.

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

## 3. Hooks Husky

- pre-commit : lint PHP des fichiers modifies.
- pre-push : Pint (--test), Larastan, `npm run test:js` (32 tests, environ 1 s : `contact-images.test.js`, `page-builder.test.js`, `media-reorder-lib.test.js`), puis Pest complet en parallele (4 processus, 210 a 340 s). Un push de suppression de branche ou de tag saute les verifications.
- Pas de --no-verify. Le hook ne controle pas les tags.

## 4. Architecture (verifiee dans le code)

Les rubriques de la passation #44 restent valables (gabarits, menu admin, themes, contact, tableau de bord, code de suppression, Page Builder, Audience, environnement, taches planifiees). Ajouts et precisions du 19/11 :

- **Redirection d'ancien slug (SEO).** Table `page_slug_redirects` (`page_id`, `old_slug`), modele `App\Models\PageSlugRedirect` (relation `page()`). `Page::resolveRouteBinding()` redirige en 301 vers l'adresse actuelle quand le slug demande est un ancien slug ET que la page cible est publiquement visible ; sinon 404 comme avant. Le hook `updated` de `Page` enregistre l'ancien slug, retire la redirection quand la page reprend un ancien slug, et pointe tous les anciens slugs directement vers la page (pas de chaine). Tests : `tests/Feature/PageSlugRedirectTest.php` (7 tests). Couvre Page seulement : Article n'a pas ce mecanisme [non verifie].
- **Ordre des medias.** `App\Traits\HasOrderedMediaCollection::reorderMedia(array $orderedIds)` (ids etrangers ignores, places du groupe conservees, renumerotation de 0 a n-1, transaction), appelee par `MediaOrderController::update()`. Retour d'erreur visible dans l'interface : logique testable dans `resources/js/admin/media-reorder-lib.js` (tests node), zone d'erreur rendue et masquee par defaut dans `x-admin.media-reorder-list`.
- **Tableau de bord.** `DashboardStatsService` (`app/Services/Dashboard/`) : les cartes Articles et Pages utilisent le scope `publiclyVisible()` (contenus programmes et brouillons exclus) ; `views_30d` supprime ; les graphiques sont groupes en SQL (`SqlDate::bucket` + `groupBy`, `DashboardSqlTest`). Test : `tests/Feature/DashboardCountsTest.php`.
- **Typage des relations Eloquent.** Larastan ne resout `$modele->relation` que si la methode a un type de retour natif ET un docblock generique : `/** @return BelongsTo<Page, $this> */ public function page(): BelongsTo`. Sans cela : `property.notFound` puis `method.notFound` sur le resultat.

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
- Audit des listes ordonnees : aucun autre endpoint n'a le defaut d'ordre du Page Builder.

Correction de la passation precedente : les graphiques du tableau de bord etaient deja calcules en SQL (ils ne l'etaient plus en memoire).

## 7. Dette et trous ouverts

- Bloc d'upload d'image duplique dans `Admin\PageController` (refactor a couvrir par test avant extraction).
- Periodes de l'Audience dupliquees par rapport a `DashboardPeriod`.
- Audience : domaine sensible a la vie privee, confidentialite a durcir.
- Redirection d'ancien slug : seulement pour les pages ; a etendre aux articles si leur slug est modifiable [non verifie].
- Condition de role de la section Raccourcis : mutation jamais faite [non verifie].
- Pest complet avec `--profile` pour isoler les tests lents.
- Commandes Artisan au role non relu [non verifie] : AuditPhantomColumnBlocks, BackfillMediaDimensions, BackfillMediaVariants, InstallWatermarkLogo, MigrateArticlesToMedia, MigrateSlidersToMedia, MigrateVideosToMedia, PruneUnusedMedia.
- Branches distantes mergees a supprimer (`git push origin --delete`) : docs/passation-19-11, docs/passation-44, feat/env-production-example, feat/favicon-logo, fix/media-reorder, fix/media-reorder-feedback, test/admin-route-access, test/audience-periode-invalide, test/blocs-separateur-citation, test/contact-alert-contenu, test/convert-webp-gain-reel, test/footer-telephone-enregistre, test/page-builder-bout-en-bout, test/page-builder-js (a verifier avec `git merge-base --is-ancestor`).

## 8. A trancher avant la mise en ligne

- Hebergement inconnu (cPanel, VPS ou autre) : conditionne le cron `schedule:run` chaque minute et la procedure (`composer install --no-dev`, `php artisan migrate --force`, `config:cache`, `view:cache`). Verifier poppler (`pdftoppm`) et les limites PHP des images jointes.
- Envoi SMTP reel jamais eprouve (seul `MAIL_MAILER=log` utilise) : plus gros risque. Le mot de passe d'application Google va dans le `.env` du serveur uniquement.
- Fuseau : Indian/Antananarivo (projet) ou Africa/Nairobi (client) ; meme decalage.
- Couleurs de marque : #E80000 / #1A1A1A contre #C41E2A / #17181A.
- Controles dans le navigateur declares faits par le client, resultats non vus : Audience, Messages, Guide (Super Admin puis Publication), favicon sur /, /login, /admin, /profile.

## 9. Ordre recommande

1. Nettoyer les branches distantes mergees.
2. Dette de code, lot par lot (upload d'image de `Admin\PageController`, periodes de l'Audience, redirection d'ancien slug pour les articles).
3. Pest complet `--profile`.
4. Trancher hebergement, SMTP, fuseau, couleurs ; deployer ; commencer par un vrai envoi d'e-mail de contact.
5. Apres la mise en ligne : themes Clair, Sombre et fetes (lots 3e, 3e-bis).

## 10. Pieges connus

- Coller deux commandes sans saut de ligne les fond en une : un seul bloc par collage, puis une Entree de plus. Ne jamais coller de sortie de tests ni de code d'un autre langage dans PowerShell hors d'un here-string.
- Un `git commit` oublie laisse les fichiers en M/A : verifier `git log` et `git status` avant de pousser.
- Nettoyer `storage\framework\testing` sans supprimer `.gitignore` (`-Exclude`).
- En .NET, `StartsWith(string)` est culturel : utiliser `StringComparison.Ordinal`.
- Windows « Acces refuse (code 5) » sur `storage\framework\views` : verrou antivirus ; exclure `storage` dans Windows Security.
- Panne DNS intermittente de la box (192.168.1.254) : « Could not resolve host: github.com ». Sans droits administrateur, forcer l'adresse pour Git dans la session : `GIT_CONFIG_COUNT=1`, `GIT_CONFIG_KEY_0=http.curloptResolve`, `GIT_CONFIG_VALUE_0=github.com:443:140.82.121.4` (a retirer apres usage ; l'adresse peut changer). Le hook rejoue Pest a chaque push : eviter de relancer sur une connexion instable.
- Le test EXIF de `ConvertExistingImagesTest` s'ignore si l'extension exif est absente (presente sur le poste du client).