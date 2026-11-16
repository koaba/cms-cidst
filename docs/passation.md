# PASSATION TECHNIQUE : cms-cidst (version du 16 novembre 2026, 14 h)

Remplace entièrement la version du 16 novembre 2026, 10 h (master 82d4759). Depuis : lots 3h-1d (emplacements de menu), 3d-4c (préréglages de couleurs) et 3h-1d-3 (réseaux sociaux) fusionnés ; lot 3f-1 (couleurs d'erreur) en cours, local seulement. La section 11 liste les écarts constatés à la relecture du code de master aa77d95.

Convention : [A VERIFIER] = non confirmé par une exécution. En cas de conflit entre ce document, le code et les tests, le code et les tests font foi.

## 0. CONSIGNES PERMANENTES POUR L'ASSISTANT (À RECOLLER EN DÉBUT DE SESSION)

Rôle attendu : développeur full stack senior, spécialiste Laravel. Code propre, précis, allégé, sans redondance. Débogage rapide et méthodique. Aussi concepteur UI/UX et développeur front-end, spécialisé dans les CMS modernes (headless / à blocs) sous Laravel, Tailwind CSS et Alpine.js : design adaptatif, options de personnalisation avancées dans le tableau de bord, optimisation pour les moteurs de recherche. Le projet doit rester évolutif et découpé en parties indépendantes (un service, un composant, un lot par sujet).

Langue de travail : français. Commits sans accents. Fichiers en LF (un CRLF fait échouer Pint).

## 1. ÉTAT DU DÉPÔT (16 novembre 2026)

Dépôt : github.com/koaba/cms-cidst (public), branche unique master (une branche de lot peut exister localement).
master = origin/master = aa77d95 (fusion --no-ff du lot 3h-1d-3 : réseaux sociaux).
Tags récents poussés : stable-2026-11-16-lot3h1d3 (aa77d95), stable-2026-11-16-lot3d4c (merge 2c5ca03), stable-2026-11-16-lot3h2 (82d4759), stable-2026-11-13-lot3h1f (26d2f8b), stable-2026-11-13-lot3h1c (b3838ff), stable-2026-11-13-lot3h1e (e45342c).
Lots fusionnés depuis la version de 10 h : 3h-1d (emplacements de menu), 3d-4c (préréglages Bordeaux, Ardoise, Sable), 3h-1d-3 (réseaux sociaux).
essai-news-ticker a été supprimée le 13/11/2026 (2 commits d'effet net nul ; le module existe sur master) ; récupérable par git reflog (10849eb) : git branch essai-news-ticker 10849eb.

Lot EN COURS : 3f-1 (couleurs d'erreur), branche locale feat/couleurs-erreur, non fusionnée, non poussée (donc absente du dépôt public : master ne contient aucun jeton danger et compte encore environ 172 occurrences de classes red-* dans 47 fichiers). Mesures : 9 tests rouges et 10 verts avant implémentation ; implémentation appliquée ; Pint, Larastan et npm run build verts ; un test Unit (contraste des erreurs) était rouge à cause de config() indisponible : correctif dans 0003-test-lit-config-themes.php-sans-demarrer-l-applicati.patch, NON ENCORE APPLIQUÉ ; mutations (8) et suite complète NON REJOUÉES.

Mesures sur master aa77d95 (annoncées par le propriétaire, non rejouées lors de la relecture du 16/11 14 h) :
- Pest 1698 passés (4917 assertions) ; Pint et Larastan niveau 5 verts sur chaque lot.
- Pushs faits avec « git push --no-verify » : le script de cycle venait de faire passer les mêmes contrôles. Cas d'exception à cause du DNS intermittent, à ne pas généraliser (voir §6).
- CI GitHub (PHP 8.3, 8.4, 8.5 ; MySQL 8.4 ; job Front) : NON CONFIRMÉE sur aa77d95 [A VERIFIER]. À regarder en priorité : les migrations (location + index des menus, show_search, youtube_url / linkedin_url, map_latitude / map_longitude) ne sont exercées sous MySQL 8.4 que par la CI.

Production : rien n'est déployé et rien ne doit l'être avant la fin du lot 3f et du lot S (sécurité, S1 et S2 au minimum). La conversion WebP est validée en local seulement.

Reste à confirmer côté propriétaire :
- Lot 3h-1d-3 : saisie des adresses YouTube et LinkedIn dans /admin/settings ; contrôle visuel du pied de page aux 5 largeurs.
- Lot 3h-1d : corriger ou supprimer dans /admin/menus les cibles /actualites, /rapports-2026 et /Menus (aucune route correspondante) [A VERIFIER].
- Lot 3d-4c : contrôle visuel des trois préréglages (public et /admin/settings).
- Lot 3h-2 : contrôle visuel de la loupe du menu à 360, 768, 1024, 1440 et 1920 px (surtout 768 px, champ ouvert), test sans JavaScript, touche Échap, case « Afficher la recherche dans le menu ».
- Lot 3h-1c : saisie de l'adresse réelle, du vrai e-mail public (encore contact@exemple.com) et du point GPS réel ; test réel du clic sur « Afficher la carte » et « Itinéraire ».
- Vérifier la CI sur aa77d95.

## 2. STACK ET ENVIRONNEMENT

Laravel 13.35, PHP 8.3 (ext-gd obligatoire), MySQL en local, Pest 4 sur SQLite en mémoire, Pint (preset laravel, v1.30.5), Larastan 3.14 niveau 5 (phpstan.neon + phpstan-baseline.neon, analyse du dossier app).
Front : Tailwind 3, DaisyUI 4.12 (admin seulement), Alpine (admin seulement), Chart.js, Vite 8. Le site public ne charge que public.css : ni Alpine ni JavaScript externe ; les scripts du public sont inline.
Paquets : spatie/laravel-permission, Intervention Image 4 (GD), setasign/fpdi + TCPDF, poppler-utils (miniatures PDF).
Poste du propriétaire : Windows, Laragon, PowerShell 5, projet C:\laragon\www\laravel-academy, base MySQL laravel_academy.
CI : PHP 8.3, 8.4 et 8.5, MySQL 8.4, job Front ; composer audit --abandoned=report.
Hooks (.husky) : pre-commit = php -l ; pre-push = php vendor/bin/pint --test, php vendor/bin/phpstan analyse --no-progress --memory-limit=1G, php vendor/bin/pest --parallel --compact. Un push qui ne fait que supprimer une branche ou envoyer un tag saute les vérifications.
Locale : APP_LOCALE=fr dans .env.example, fallback_locale = en. Les messages de validation sans clé dans lang/fr/validation.php s'affichent en anglais : ajouter la clé française quand on introduit une règle nouvelle.

## 3. MÉTHODE DE TRAVAIL (CYCLE D'UN LOT)

1. Tests d'abord. Patch de tests (lotX-1-tests.patch), vus rouges. Les garde-fous qui restent verts sont annoncés à l'avance. Commit « test : ... (lot X) ».
2. Compter les rouges ET les verts réellement mesurés avant d'implémenter. Tout vert non annoncé est analysé (voir §5, « tests d'absence creux »).
3. Implémentation (lotX-2-impl.patch) : application, Pint, Larastan, tests ciblés, php artisan migrate si migration, npm run build si une classe Tailwind change, suite complète.
4. Mutations (7 pour un changement d'une condition, 15 à 25 pour un lot large ; bloc séparé) : une mutation survivante = test creux ou bug, sauf une survivante purement visuelle annoncée à l'avance. Chaque motif de mutation doit être unique dans son fichier (le script vérifie le nombre d'occurrences et ignore le motif sinon : une mutation absente de la sortie = à refaire, pas à compter comme tuée).
5. Liste des fichiers modifiés = liste attendue ; git add explicite fichier par fichier ; un commit « feat: ... (lot X) ».
6. Fusion --no-ff sur master, un seul push (le hook dure 2 à 7 minutes), vérification master = origin/master après git fetch, git branch -d, tag annoté stable-AAAA-MM-JJ-lotX (date du jour de la fusion) poussé séparément. Jamais de squash.
7. Côté propriétaire : migrate si migration, cache:clear (config:clear en production), contrôle visuel aux 5 largeurs, vérification de la CI.

Chaque lot design : mobile-first, paliers Tailwind par défaut, contrôle à 360/768/1024/1440/1920 px, test de contraste, test interdisant les couleurs en dur.

Assistance IA. Claude n'a pas accès au poste du propriétaire. Il livre des patchs et des FICHIERS .ps1 (à déposer dans Téléchargements), jamais de long bloc à coller : le collage d'un long bloc fait planter PSReadLine. Lancement : powershell -ExecutionPolicy Bypass -File "$env:USERPROFILE\Downloads\<fichier>.ps1". Les .ps1 sont en ASCII pur (PowerShell 5 lit un fichier sans BOM en ANSI), avec contrôle de $LASTEXITCODE à chaque étape. Si un script s'arrête, la branche reste en l'état : ne jamais relancer un script de départ dont la branche existe, ni réappliquer un patch ; les scripts de fusion/push sont reprenables (ils reprennent selon la branche courante et l'état du dépôt).
Ce que l'assistant peut valider de son côté : php -l, Pint (phar obtenu par git clone --depth 1 --branch v1.30.5 https://github.com/laravel/pint, fichier builds/pint, avec --config pint.json), application des patchs sur un clone neuf de master + le commit de tests, scripts PHP autonomes avec classes factices. Il ne peut pas installer Composer, donc ni Pest ni Larastan : les comptes de tests qu'il annonce sont raisonnés, pas mesurés. Il peut lire le dépôt public en clone (lecture seule, pas de push). Les fichiers joints au chat arrivent par pièce jointe.
Un développeur humain suit le même cycle avec git normal.
Conventions de commit : trailers « Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com> » et « Claude-Session: <URL de la session en cours> » en un seul paragraphe final. L'URL de session change d'une session à l'autre : utiliser celle de la session courante. Dernière utilisée : https://claude.ai/code/session_01KWCRuLJEN67X8B8zzPnqEd.

## 4. ARCHITECTURE À CONNAÎTRE

### 4.1 Mise en page et admin
- Layout public : components/layout.blade.php contient <x-news-ticker>, <x-main-menu>, <main>, <x-footer>.
- Admin : <x-admin.layout> ; menu dans config/admin_menu.php ; rôles dans App\Enums\UserRole. Seul le Super Admin accède à : menus, catégories, PDF, réglages, audience, messages, utilisateurs (filtrage par middleware de route).
- Page Builder : blocs en base (table page_blocks), services dans app/Services/PageBuilder/, règles dans app/Blocks/Rules/. Médias par la table mediables (aucun chemin d'image brut dans les blocs).
- Caches : App\Support\PublicPageCache met en cache l'ARBRE DE DONNÉES d'une page (seo, médias, blocs, PDF), jamais le HTML : le menu, le pied de page et les réglages sont donc rendus à chaque requête et un changement de réglage s'applique tout de suite. App\Support\PublicLayout met en cache le menu, le ticker et la barre latérale. Observers PublicPageCacheObserver et PublicLayoutCacheObserver dans AppServiceProvider. La page Contact n'est pas dans le cache de pages (vue de contrôleur).

### 4.2 Thème
- Un seul fichier de jetons tailwind.tokens.js, 7 rôles : primary #C41E2A, secondary #17181A, bg #F1F2F4, surface #FFFFFF, text #17181A, muted #4B5057, border #D8DADE. Variables CSS --color-X-rgb. Classes utilitaires cidst-* (bg-cidst-bg, text-cidst-text, bg-cidst-primary, text-cidst-primary-content, border-cidst-border...).
- Aucune couleur gray-* en dur (test NoHardcodedGrayTest ; ThemeTokenUsageTest). Les accesseurs SiteSetting::primary_color et secondary_color ne servent jamais à générer le thème.
- Services App\Services\Theme\* : ThemeRoles, ThemePalette, ThemeStyleBuilder, ContrastGuard (bloque l'enregistrement si le texte principal passe sous 4,5:1 sur le fond ou la surface ; les autres écarts sont signalés après coup).
- Résolution des couleurs par couches, la dernière l'emporte (ThemeStyleBuilder::roles) : défauts -> réglages historiques (primary_color, secondary_color) -> palette du thème (config/themes.php) -> couleurs personnalisées (site_settings.theme_colors, JSON). ContrastGuard ne bloque que le texte principal sous 4,5:1.
- Préréglages (lot 3d-4c) : config/themes.php contient « cidst » (défaut, sans palette), Bordeaux, Ardoise et Sable. Une palette fournit les 7 rôles, « accent » (pastilles du formulaire des réglages) et primary_content / secondary_content / accent_content (texte posé, >= 4,5:1). Test : ThemePresetsTest. Les préréglages personnalisés NOMMÉS sont reportés : une seule copie personnalisée existe (theme_colors).
- Couleurs d'état (lot 3f-1, en cours) : trois jetons FIXES, hors thème (un message d'erreur reste rouge quel que soit le préréglage) : danger #B91C1C, danger-soft #FEE2E2, danger-content #991B1B. Source prévue : ThemeRoles::statusDefaults() et variables --color-danger-rgb, --color-danger-soft-rgb, --color-danger-content-rgb dans resources/css/shared.css. Ne pas passer par theme_colors ni ThemeStyleBuilder. Hors périmètre : badges de catégories (Category.php, imposés par CategoryBadgeColorTest) ; verts, bleus, oranges (lot 3f-1b).
- Le thème DaisyUI « cidst » de l'admin est encore en hexadécimal figé (tailwind.config.js ; lot 3f-2).

### 4.3 Réglages du site
- Modèle App\Models\SiteSetting. SiteSetting::current() est en rememberForever (clé site-settings.current) et mémoïsé dans le conteneur ; invalidé à l'enregistrement (événements saved/deleted).
- Une colonne ajoutée n'existe pas dans le modèle en cache avant cache:clear : le code doit traiter une valeur absente comme « vide » (ou « affiché » pour les cases à cocher : ?? true).
- DB::table() et les ->update() de requête ne déclenchent pas les observers : appeler PublicPageCache::forgetAll() et PublicLayout::forget() à la main.
- SiteSettingController (edit, update, resetColors), SiteSettingService (constante BOOLEAN_FIELDS qui force à false les cases absentes : video/image/pdf/diaporama watermark, contact_images_enabled, show_footer, show_search, footer_show_contacts ; les champs NOT NULL vides sont retirés avant l'update : contact_images_max_mb, logo_height ; theme_colors normalisés en majuscules), Admin\UpdateSiteSettingRequest.
- Une case décochée doit le rester après une erreur de validation : $footerChecked('champ') dans edit.blade.php = session()->hasOldInput() ? (bool) old($f) : (bool) ($settings->{$f} ?? true) ; réutiliser cette closure pour toute nouvelle case.
- NE PAS ajouter map_latitude / map_longitude à la liste « vide = ne rien changer » du service : c'est ce qui permet d'effacer le point GPS (garde-fou testé et muté, mutation M25).

### 4.4 Coordonnées de l'établissement : source unique (lot 3h-1c)
App\Services\Contact\SiteContact, construite sur un SiteSetting (new SiteContact($settings) ou SiteContact::current()). Un seul interrupteur, footer_show_contacts, masque tout (pied de page, encart de la page Contact, carte, lien « Nous trouver », JSON-LD local).
Méthodes :
- shown() : interrupteur (absent du modèle en cache = affiché).
- email(), facebook() (http/https seulement), addressLines(), hoursLines() : une ligne par retour à la ligne, vides retirées.
- phones() : contact_phone puis contact_phone_2, libellé tel que saisi, href tel: nettoyé ; moins de 5 chiffres = ignoré.
- whatsapp() : exige le « + » initial et au moins 7 chiffres ; lien https://wa.me/<chiffres>.
- has() : au moins un moyen de contact (adresse, téléphone, WhatsApp, horaires, e-mail, Facebook). Le GPS n'y compte pas.
- hasDetails() : adresse, téléphone, WhatsApp ou horaires (sans e-mail ni Facebook ni GPS) ; sert à la condition d'affichage de l'encart.
- coordinates() : ['lat' => float, 'lng' => float] ou null. Les deux valeurs numériques et finies, latitude dans [-90, 90], longitude dans [-180, 180], zéro accepté, null si l'interrupteur est décoché.
- directionsUrl() : https://www.google.com/maps/dir/?api=1&destination=-18.9137%2C47.5361
- osmEmbedUrl() : https://www.openstreetmap.org/export/embed.html?bbox=47.5311%2C-18.9187%2C47.5411%2C-18.9087&layer=mapnik&marker=-18.9137%2C47.5361 (bbox = point ± 0,005, décimales superflues supprimées, 7 décimales au plus).
Base : colonnes map_latitude et map_longitude, decimal(10,7) nullable (migration 2026_11_13_140000), fillable, cast float.
Saisie admin : champs texte (inputmode decimal) après contact_hours dans le fieldset « Coordonnées de l'établissement » ; la virgule décimale est acceptée (prepareForValidation remplace , par . et rogne les espaces) ; règles : nullable|numeric|between|decimal:0,7|required_with:<l'autre champ> (les deux ou aucun). Messages en français dans lang/fr/validation.php.

### 4.5 Pied de page (App\View\Components\Footer, components/footer.blade.php, footer-icon.blade.php)
- Délègue toutes les coordonnées à SiteContact (propriété privée $contact) en gardant ses méthodes publiques : description, showContacts, email, facebook, addressLines, hoursLines, phones, whatsapp, hasContacts, locationUrl, ctaActions, copyright, logoSrc, logoSize.
- Bandeau d'appel #footer-cta (Appeler = premier numéro, WhatsApp, Écrire) ; grille 12 colonnes (4/3/5 avec coordonnées, 6/3 sans) ; pastilles d'icônes ; barre du bas avec « Haut de page ». Une seule <img> (le logo, en lazy). Interrupteur show_footer pour tout le pied de page.
- Lot 3h-1f : ctaActions() n'ajoute pas « Écrire » quand request()->routeIs('contact.show') (il pointerait vers la page courante). S'il était la seule action, le bandeau entier disparaît (@if($ctaActions())). Ne pas mettre la page Contact dans un cache partagé avec d'autres routes : le contenu du pied de page dépend de la route.
- <nav aria-label="Pied de page"> : Contact, Rechercher, puis « Nous trouver » (lien route('contact.show').'#nous-trouver') seulement si coordinates() n'est pas null. Le seul point GPS ne fait pas apparaître la colonne « Nous joindre ».

### 4.6 Page Contact
- ContactController::show (vue public/contact.blade.php, conteneur max-w-xl) passe 'contact' => new SiteContact($settings) ; ContactMessageRequest valide l'envoi ; store répond avec un honeypot (champ website) et une alerte e-mail différée.
- Composant anonyme resources/views/components/contact/location.blade.php, inclus après le formulaire : <section id="coordonnees"> affichée si hasDetails() ou GPS valide. Contenu : adresse, téléphones, WhatsApp, horaires ; JAMAIS l'e-mail. Si GPS : <div id="nous-trouver"> avec <button type="button" data-map-src="..."> « Afficher la carte », lien « Itinéraire » (target="_blank" rel="noopener noreferrer"), mention OpenStreetMap, et un script inline qui crée l'iframe au clic. Aucune iframe ni requête OpenStreetMap avant le clic. Tous les liens et boutons ont min-h-[44px].
- L'id contact_public_email est réservé à la phrase « Vous pouvez aussi envoyer un PDF... » de la page Contact.

### 4.7 SEO
- SeoService : titres, descriptions, canonical, images, JSON-LD (Article = NewsArticle, Page, fil d'Ariane, accueil = @graph Organization + WebSite). jsonLd() échappe < > & pour qu'une valeur ne referme jamais la balise script. Rendu par partials/seo-jsonld.blade.php. Textes des pages sans modèle dans config/seo.php.
- Nœud Organization de l'accueil = ['@id'] + organization() + localData(). localData() ajoute, seulement si renseignés : address (PostalAddress, addressCountry = config('seo.organization.country'), défaut MG), telephone, email, geo (GeoCoordinates). Les horaires libres ne sont pas émis.

### 4.8 Sécurité
- config/security.php : CSP, mode par env CSP_MODE (défaut report-only ; enforce à activer en production). frame-src = https://www.youtube.com https://player.vimeo.com https://www.openstreetmap.org (dans cet ordre), aligné sur App\Support\VideoEmbed. script-src garde 'unsafe-inline' et 'unsafe-eval' (dette connue).
- Dossier du lot S (sécurité) : docs/lot-s-securite.md (à lire dans une session dédiée).

### 4.9 Recherche dans le menu (lot 3h-2)
- Composant anonyme resources/views/components/menu-search.blade.php (@props id, inputId) : <form method="get" action="route('search')" role="search">, <label class="sr-only">Rechercher</label>, <input type="search" name="q" maxlength="255">, bouton submit loupe SVG (aria-label="Rechercher", min-h-[44px] min-w-[44px], svg aria-hidden). Couleurs du thème uniquement.
- main-menu.blade.php : formulaire bureau id="menu-search" (champ id menu-search-q) à l'extrémité droite (le bloc des liens passe en md:ml-auto), formulaire mobile id="menu-search-mobile" (champ menu-search-q-mobile) en haut du menu déplié. Tout est sous @if($settings->show_search ?? true).
- Script inline : replie le champ bureau derrière la loupe (classe md:hidden ajoutée par le JS, aria-expanded/aria-controls), l'ouvre au clic avec focus, le referme avec Échap ou au clic si le champ est vide. Sans JavaScript, le champ reste visible et la recherche fonctionne.
- Réglage show_search : boolean, défaut true (migration 2026_11_13_160000), fillable, cast, BOOLEAN_FIELDS, règle nullable|boolean, case « Afficher la recherche dans le menu » dans le fieldset « Menu public » de /admin/settings.
- SearchController (GET /recherche, nom search) : validation q nullable|string|max:255 ; recherche LIKE (App\Support\Like, jokers échappés) sur le TITRE des articles et des pages visibles, 20 résultats maximum chacun.
- Tests : MenuSearchTest (22 tests). Mutations : 23 tuées sur 24 ; M16 (retirer md:ml-auto) survit, purement visuelle. Le comportement du script en ligne n'est pas testé (voir §7).

### 4.10 Menus et emplacements (lot 3h-1d)
- Table menus : colonne location string(20), défaut 'main', index (location, is_active, order). Valeurs : main, footer, legal. CONSTANTES, pas d'enum : Menu::MAIN et Menu::LOCATIONS.
- Menu::activeByLocation() : une requête -> main (arbre), footer et legal (listes). Menu::activeTree() : arbre du menu principal.
- Menu::resolved_url : un nom de route donne route() (URL absolue), sinon SafeUrl::sanitize(target) ('#' si refusé). Menu::is_external : vrai seulement pour http(s):// qui n'est pas un nom de route.
- PublicLayout expose menu(), footerMenu(), legalMenu() ; un cache antérieur sans ces clés donne une liste vide. Footer : links() et legalLinks().
- Validation (Admin\MenuRequest) : target via App\Rules\SafeLinkUrl ; location dans Menu::LOCATIONS ; footer et legal sont PLATS ; le parent d'un sous-menu doit être du menu principal ; un élément qui a des sous-menus ne peut pas quitter le menu principal.
- Dette : les liens fixes du pied de page (Contact, Rechercher, Nous trouver) sont encore en dur dans footer.blade.php.

### 4.11 Réseaux sociaux (lot 3h-1d-3)
- App\Services\Contact\SocialLinks : Facebook (délégué à SiteContact::facebook()), YouTube (youtube_url), LinkedIn (linkedin_url), dans cet ordre. Même interrupteur que les coordonnées (footer_show_contacts).
- Seules les adresses http(s) sortent : validation url:http,https ET filtre à l'affichage avec SafeUrl. WhatsApp reste une coordonnée (SiteContact::whatsapp) et n'est PAS répété dans SocialLinks.
- Affichage : Footer::socials() et boucle dans components/footer.blade.php. YouTube et LinkedIn utilisent l'icône générique « lien » (components/footer-icon.blade.php) en attendant de vraies icônes de marque.
- Migration 2026_11_16_180000 : noms de colonnes EN TOUTES LETTRES, car Larastan déduit les propriétés d'un modèle des noms écrits dans database/migrations ; une boucle ou une constante de noms donne property.notFound.

## 5. RÈGLES DE TEST À RESPECTER

- Pest sur SQLite en mémoire, cache array, QUEUE_CONNECTION=sync. RefreshDatabase sur tests/Feature. Helpers globaux : adminUser(), publicationUser(), setDeleteCode().
- Un helper défini dans un fichier de test est global : le préfixer par fichier (siteContact..., mapSettings..., contactLocation..., publicFooterLocation..., seoLocal..., footerContactCta..., menuSearch...). Les constantes globales aussi. Jamais de helper visit(). Les helpers reçoivent $test en paramètre.
- $test->blade() est protected : utiliser Blade::render('<x-...>'). Blade::render n'a PAS de route courante : un comportement qui dépend de la page (routeIs) se teste par une vraie requête ($this->get(...)). Pour une vue de composant avec un modèle sans la colonne : view('components.main-menu', ['items' => collect(), 'settings' => new SiteSetting])->render(). Pas de DDL dans un test. Pas d'horloge réelle : $this->travelTo(...). Accents en PHP de test : \u{e9} entre guillemets doubles ; titres de tests sans accents.
- Lire un message de validation : putJson puis ->json('errors')['cle.avec.point'][0].
- Un jeu de données compte un test par cas. Une assertion de page entière casse dès qu'un composant global ajoute un élément : la restreindre à la balise visée (regex sur la section, par exemple <nav class="bg-cidst-surface...>, <section id="footer-cta">). Le menu public contient désormais <form id="menu-search"> et un <input name="q"> sur toutes les pages.
- {{ }} encode & en &amp; dans les attributs : comparer avec e($url). La sous-chaîne src=" est aussi contenue dans data-map-src=" (utiliser src=" avec espace). Le premier <button type="button"> d'une page est celui du menu mobile.
- Tests d'absence creux : un test « n'affiche pas X » passe tant que la fonctionnalité n'existe pas. Lors de l'état rouge, les verts non annoncés se comptent et se classent (garde-fou existant, absence vacue, test creux). Seules les mutations prouvent qu'un test d'absence protège vraiment : le coupler à une assertion positive sur la même page, et le faire tuer par une mutation qui retire sa condition.
- Fichiers du lot 3h-1c : ContactPageLocationTest, MapCoordinatesSettingsTest, PublicFooterLocationTest, SeoLocalJsonLdTest, SiteContactTest, SecurityHeadersCspTest. Lot 3h-1f : FooterContactCtaTest (4). Lot 3h-2 : MenuSearchTest (22). Suite complète : 1698 sur aa77d95 (annoncé, non rejoué le 16/11 à 14 h).
- LES TESTS DE tests/Unit/ NE DÉMARRENT PAS L'APPLICATION : ni config(), ni facades, ni base. Lire un fichier de config avec require, ou placer le test dans tests/Feature/. Un test rouge « méthode inexistante » peut cacher ce défaut (cas du lot 3f-1).

## 6. PIÈGES PowerShell, Blade, git

PowerShell 5 :
- Ne JAMAIS coller un long bloc dans la console : PSReadLine plante (« La valeur réelle était -1 »). Utiliser un fichier .ps1 (§3).
- vendor\bin\pest, pint et phpstan sont des .bat : cmd.exe relit le « | » d'un --filter comme un pipe. Toujours appeler « php vendor\bin\pest "--filter=A|B" ». Un argument natif contenant | doit être entièrement entre guillemets.
- Ne jamais considérer un code de sortie non nul comme « rouge attendu » sans vérifier qu'un résumé « Tests: » existe.
- Fins de ligne avec `n (jamais [Environment]::NewLine). Pas de $ErrorActionPreference='Stop' avec git.
- L'opérateur > écrit en UTF-16 : utiliser git diff --output=<fichier> (et --result-file pour mysqldump).
- La note « Using configuration file » de Larastan s'affiche comme une erreur : lire [OK] No errors.
- Le rapport JUnit de Pest (--log-junit) donne l'attribut file sous la forme « chemin::nom du test » : couper d'abord avec -split '::'.
Blade :
- Dans une vue qui contient déjà un @php(...) en ligne, ne jamais ajouter un bloc @php ... @endphp. Pour du code Blade à logique, préférer un composant (classe ou @props) sans bloc @php.
- Un @if placé au milieu d'une balise laisse un double espace : pour des attributs exacts, utiliser {!! cond ? ' target="_blank"' : '' !!}.
- Le test PublicPageShowMediaImgTest ne cherche le lazy que sur l'img de la page : le logo du pied de page est volontairement lazy.
Git :
- Après un push incertain : git fetch puis git log origin/master..master avant de relancer. Ne jamais supprimer une branche avant fusion vérifiée sur origin.
- Un push de tag ne lance pas les vérifications du hook.
- « Could not resolve host: github.com » après un hook long : le DNS de la machine coupe pendant l'attente. Vérifier Test-NetConnection github.com -Port 443 (ipconfig /flushdns, autre réseau, VPN coupé). Seulement si les trois contrôles viennent de passer sur le même commit, sans modification entre-temps : git push --no-verify origin master, puis relancer le script de fusion (reprenable).

## 7. DETTE TECHNIQUE

- Le script en ligne de la loupe (repli, Échap, aria-expanded) n'est testé que dans son existence : le sortir dans resources/js/public/ avec un test Vitest (le dossier en a déjà) ; naturel au lot 3g.
- La recherche ne porte que sur les titres (articles et pages), pas sur le contenu.
- views_30d encore calculé ; graphiques du tableau de bord en groupBy en mémoire ; carte « Pages » qui compte les programmées ; AudienceService::monthly() groupe par DATE(visited_at) ; pas de redirection depuis un ancien slug ; upload dupliqué dans Admin\PageController ; suivi des visites seulement sur blog.show (filtre des robots à faire) ; 6 avertissements Permissions-Policy en console (lot séparé).
- PagePublicDisplayTest contient assertDontSee('<img') qui ne peut jamais échouer (test creux préexistant). media:convert-webp sans reprise sur WebP orphelin.
- Le thème DaisyUI « cidst » est figé en hexadécimal (lot 3f-2) ; environ 172 occurrences de classes red-* (47 fichiers) à passer sur les jetons (lot 3f-1), puis bleus, verts, oranges en dur dans les vues admin (lot 3f-1b).
- Icônes YouTube et LinkedIn génériques ; liens fixes du pied de page encore en dur.
- L'iframe OpenStreetMap est créée sans attribut sandbox ni referrerpolicy ; la CSP est en report-only par défaut.
- Validation : seules les règles ayant une clé dans lang/fr/validation.php sont en français.
- Sécurité : voir docs/lot-s-securite.md (mots de passe min:8 seulement, ranking() avec colonne interpolée dans selectRaw, 12 usages de {!! !!}, APP_DEBUG=true dans .env.example, pas de Dependabot ni npm audit en CI, CSP avec unsafe-inline/unsafe-eval).

## 8. PRODUCTION À PRÉPARER

.env, storage:link, watermark:install-logo, cron schedule:run, worker supervisé, sauvegardes avec copie hors serveur, supervision des erreurs, CSP en enforce (CSP_MODE=enforce), trustProxies. Après chaque déploiement : config:clear puis cache:clear ; migrate pour les colonnes ajoutées (map_latitude / map_longitude du lot 3h-1c, show_search du lot 3h-2 et celles des lots suivants).
Procédure WebP : sauvegarde vérifiée (base + storage), déploiement, config:clear puis cache:clear, media:convert-webp en simulation, --force --limit=5, contrôle visuel, passage complet. Restauration : base et storage ensemble.
Sauvegardes : ne jamais restaurer depuis storage-app-2026-11-11 (incomplète). Références : C:\sauvegardes\cidst\base-2026-11-11-avant-conversion.sql et storage-app-2026-11-11-avant-conversion (1076 fichiers, 344 977 234 octets).
Avant toute mise en production : lot S (sécurité), au moins S1 (configuration) et S2 (dépendances), et un test d'intrusion externe de préférence.

## 9. PLAN RESTANT (ORDRE À RESPECTER)

0. Immédiat : finir 3f-1 (appliquer le patch 0003, relancer lot3f1-cycle.ps1 depuis feat/couleurs-erreur, contrôle visuel des erreurs, fusion, tag stable-2026-11-16-lot3f1) ; CI verte sur master ; corriger ou supprimer les cibles de menu /actualites, /rapports-2026, /Menus ; renseigner YouTube et LinkedIn.
1. 3f-1b : verts, bleus, oranges en dur -> jetons d'état (success, info, warning) sur le modèle de 3f-1.
2. 3f-2 : DaisyUI de l'administration branché aux variables du thème.
3. 3f-3 : Chart.js sensible au thème.
4. 3e : thèmes Clair et Sombre sur le public (le sombre n'est activable qu'après 3f).
5. 3e-bis : fêtes (Noël, Pâques par algorithme sans ext-calendar, Pentecôte, Indépendance 20-26 juin, Fête du Travail ; liste à confirmer). Zone réservée aux icônes de fête déjà prévue en haut du pied de page.
6. 3g : composants façon shadcn/ui et style Netflix (<x-button>, carrousel) ; bundle public.js avec Alpine ; script de la loupe en fichier testé.
7. Lot S : sécurité, en session dédiée (docs/lot-s-securite.md : S1 configuration, S2 dépendances, S3 mots de passe, S4 injections, S5 accès, S6 uploads, S7 chiffrement, S8 CSP, S9 détection). S5 doit traiter le point 2 de la section 11.
8. Lot 4 : PDF allégés avec Ghostscript. Lot 5 : vidéos WebM avec ffmpeg en tâche de fond (dépend du VPS).

Décisions prises : garde-fou de contraste = bloquer le texte principal sous 4,5:1 et avertir pour le reste ; défauts #C41E2A / #17181A ; thème Itadori retiré ; Contact dans le pied de page, pas de bouton rouge dans le menu ; recherche en loupe à l'extrémité droite du menu, formulaire GET qui marche sans JavaScript, réglage show_search ; un seul interrupteur pour toutes les coordonnées ; carte OpenStreetMap au clic (pas de Google en iframe) ; pas d'horaires dans le JSON-LD ; e-mail dans le JSON-LD mais pas dans l'encart de la page Contact ; pays du JSON-LD dans config/seo.php (MG) ; emplacements de menu en constantes (main, footer, legal) ; WhatsApp reste une coordonnée ; préréglages de couleurs fixes en config (copies personnalisées nommées reportées) ; couleurs d'état fixes, hors theme_colors ; bouton « Écrire » du bandeau masqué sur /contact seulement ; branche essai-news-ticker supprimée.
Questions ouvertes : confirmation de la liste des fêtes ; adresse, e-mail public et coordonnées GPS réels du CIDST (à saisir dans /admin/settings, jamais dans le code).

## 10. REPRISE IMMÉDIATE

1. Vérifier l'état : git status -sb, git fetch origin, git log --oneline -8. Attendu : master = origin/master = aa77d95 (ou le commit de fusion des docs qui le suit), tag stable-2026-11-16-lot3h1d3 présent ; la branche feat/couleurs-erreur peut exister localement.
2. php artisan migrate ; php artisan config:clear ; php artisan cache:clear ; php artisan app:check-environment.
3. php vendor/bin/pest --parallel --compact (3 à 7 minutes ; attendu 1698 sur master).
4. Vérifier la CI GitHub sur le dernier commit de master (priorité : jobs MySQL 8.4).
5. Si feat/couleurs-erreur existe : reprendre le lot 3f-1 (§1) avant tout autre lot, selon la méthode du §3.
6. Lire docs/lot-s-securite.md avant tout travail de sécurité, et la section 11 ci-dessous.

## 11. ÉCARTS CONSTATÉS À LA RELECTURE DU CODE (master aa77d95, 16 novembre 2026, 14 h)

Lecture statique d'un clone du dépôt public ; rien n'a été exécuté (Composer n'a pas pu télécharger les dépendances dans l'environnement de relecture : Pest, Pint et Larastan non rejoués).

1. Ce fichier docs/passation.md était resté à la version de 10 h (master 82d4759) : remplacé par la présente version.
2. Le middleware confirm.delete protège les suppressions des articles, pages, sliders, news-tickers et blocs (routes web.php). Il n'est PAS posé sur les suppressions de menus, catégories, catégories PDF, documents PDF et utilisateurs (groupe Super Admin). À décider : l'ajouter, ou documenter que le rôle suffit. À traiter au lot S5 (matrice d'accès).
3. La ressource admin/users (Super Admin) existe dans les routes, avec la route PATCH reset-password ; elle ne figurait pas dans les listes de routes du résumé.
4. Storage::disk('public')->delete() est appelé à la main dans MediaSyncService (syncCoverImage, remplacement d'une vidéo téléversée), ImageOptimizer et MediaUploader, alors que la règle est de passer par HasOrphanMediaCleanup::detachAndPruneOrphanMedia(). Seuls les événements de modèle (Media, Video, ContactAttachment) sont légitimes. À auditer : certains appels peuvent être justifiés (fichier pas encore rattaché à un média).
5. AudienceService::ranking() interpole {$column} dans selectRaw, mais ses trois appelants passent des littéraux (path, referrer_host, device) : pas d'injection aujourd'hui. À fermer par une liste blanche dans la méthode et un test (lot S4).
6. phpstan-baseline.neon fait 571 lignes : « Larastan niveau 5 vert » inclut cette baseline. À compter et à résorber progressivement.
7. Les 13 usages de {!! !!} relevés (sitemap, JSON-LD, theme-style, attributs target, logo, media-img, json_encode, nl2br(e())) échappent correctement leurs valeurs. Le CSS du thème est sûr : chaque couleur est validée en #RRGGBB à la saisie et re-validée par ThemeStyleBuilder.
8. composer.json exige laravel/framework ^13.8 ; la version installée (13.35) vient du composer.lock.
