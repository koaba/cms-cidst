# cms-cidst : dossier du lot S (sécurité)

À coller dans une session dédiée, avec la passation technique (méthode §3 : tests d'abord, mesures, mutations, un lot par sujet).
Base : master 26d2f8b. Constat issu d'une lecture statique rapide du dépôt (pas de test d'intrusion, rien exécuté). Chaque point « à vérifier » doit être confirmé par un test, pas par confiance.

## 1. Déjà en place (à conserver, à protéger par des tests)

- Mots de passe : cast `hashed` sur User, aucun config/hashing.php (donc bcrypt par défaut), aucun MD5.
- Injection SQL : Eloquent paramétré ; `Like::contains` échappe les jokers (`escape '!'`).
- XSS : sorties `{{ }}` ; `nl2br(e())` pour le texte ; JSON-LD échappé (`< > &`) ; `SafeUrl` refuse `javascript:`, `data:`, `//hote`.
- Limitation de débit : login, mot de passe oublié, vérification, formulaire Contact, code de suppression (`DeleteCodeVerifier`).
- Honeypot sur le formulaire Contact. Fillable explicites (pas de `$guarded = []`).
- En-têtes (`SecurityHeaders`) : X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy, CSP (report-only).
- Uploads : `mimes` stricts, aucun SVG, images ré-encodées (GD), limites de taille.
- Accès : rôles par middleware de route ; dernier Super Admin protégé.
- Chaîne d'approvisionnement : `composer audit --abandoned=report` en CI ; lockfiles commités ; `npm install --ignore-scripts` dans le script setup ; site public sans JavaScript externe.

## 2. Plan par sous-lots (ordre conseillé : risque décroissant, effort croissant)

### S1 Configuration production (mauvaise config)
Risque : débogage ouvert, `.env` lisible, cookies non sécurisés.
- `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` présent et sauvegardé à part ; `.env` hors du dossier public, droits 640 ; document root = `public/` ; refuser `.git`, `.env`, `storage` côté serveur web.
- `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `SESSION_SAME_SITE=lax`, HTTPS forcé, HSTS, `trustProxies`.
- `composer install --no-dev --optimize-autoloader` en production ; `CSP_MODE=enforce` après observation des rapports.
- Réutiliser `Services\Environment\EnvironmentChecker` : ajouter les contrôles ci-dessus (avertissement dans l'admin si l'un échoue). Test : un jeu de configurations dangereuses doit produire des avertissements.

### S2 Dépendances piégées, paquets npm/composer piratés
- Dependabot (composer, npm, github-actions) ; `npm audit --omit=dev` et `npm audit signatures` en CI ; `npm ci` (jamais `npm install`) en CI ; actions GitHub épinglées par SHA.
- `composer.json` : `config.allow-plugins` en liste fermée ; examiner la clé `audit.block-insecure` selon la version de Composer.
- Règle de revue : tout nouveau paquet = nom exact (typosquatting), mainteneur, ancienneté, nombre de téléchargements, scripts d'installation. Interdire les scripts npm (`ignore-scripts=true` dans `.npmrc`).
- Pas de CDN externe ; si un jour il en faut un : SRI + CSP.
- Test : un test de fichiers vérifie `.npmrc`, les lockfiles présents et l'absence de `<script src="http` dans les vues.

### S3 Authentification et mots de passe
Constat : seule la règle `min:8` existe (UserRequest, ResetUserPasswordRequest).
- `Password::defaults()` : 12 caractères minimum, mixte, chiffres, `uncompromised()` ; l'appliquer partout (création, réinitialisation, changement).
- Test : le hash stocké commence par `$2y$` ou `$argon2` ; `Hash::needsRehash` ; jamais MD5/SHA1.
- Double authentification (TOTP) pour le Super Admin ; limitation par couple e-mail + IP ; expiration de session sur inactivité ; journal des connexions et des échecs.
- `must_change_password` existe déjà : s'en servir à la première connexion.

### S4 Injection de code dans les formulaires
- `AudienceService::ranking()` interpole `{$column}` dans `selectRaw` et `groupBy` : prouver par test que la colonne vient d'une liste fixe (jamais de saisie).
- 12 usages de `{!! !!}` à auditer un par un : `theme-style` (`$css`) ne doit contenir que des couleurs hexadécimales validées (test : une valeur `</style><script>` est refusée) ; `media-img` (`$extra`) ne doit jamais recevoir de saisie ; les `json_encode` gardent les drapeaux `JSON_HEX_*`.
- Suite de charges hostiles (XSS, SQL, CRLF, Unicode bidirectionnel) envoyée à chaque champ public et admin ; vérifier l'échappement en sortie.
- Injection d'en-tête e-mail : nom/sujet du formulaire Contact avec `\r\n` (alerte e-mail différée).
- Si un éditeur de texte riche arrive un jour : liste blanche de balises (HTMLPurifier), jamais de confiance au HTML du navigateur.

### S5 Accès : matrice automatique
- Un test parcourt toutes les routes admin : invité = redirection login ; rôle insuffisant = 403 ; rôle suffisant = accès. Une route ajoutée sans protection fait échouer la suite.
- IDOR : un éditeur ne doit pas lire ni modifier le contenu d'un autre sans policy.

### S6 Uploads
- Vérifier la signature réelle des fichiers (`%PDF`, en-têtes image), noms générés (pas le nom d'origine), stockage sans exécution de PHP (serveur : aucun moteur PHP sous `storage`).
- Images envoyées par des visiteurs (formulaire Contact) : surface élevée ; envisager ClamAV et un délai de quarantaine.
- Futur lot 5 (ffmpeg) : `Process` avec tableau d'arguments, jamais une chaîne de shell construite avec un nom de fichier.
- Téléchargement des pièces jointes (`fpassthru`) : `Content-Disposition`, `nosniff`, contrôle de rôle (déjà en place : à tester).

### S7 Chiffrement
- `APP_KEY` : jamais commité ; rotation avec `APP_PREVIOUS_KEYS`. Ne jamais écrire de chiffrement maison.
- TLS 1.2+ avec renouvellement automatique ; HSTS ; connexion MySQL via un utilisateur à privilèges minimaux (jamais root), en TLS si distant.
- Sauvegardes chiffrées (age ou gpg), copie hors serveur, restauration testée.
- Données personnelles des messages de contact : cast `encrypted` à étudier ; aucun secret en clair dans `site_settings`.
- Test : redirection HTTP vers HTTPS, cookie `Secure`, session chiffrée.

### S8 CSP stricte
- Aujourd'hui : `unsafe-inline` et `unsafe-eval` (dette connue). Plan : nonce par requête généré dans `SecurityHeaders`, attribut `nonce` sur chaque script en ligne (menu, carte, thème) ; `frame-ancestors`, `base-uri`, `form-action` ; retirer `unsafe-eval` quand Alpine passera au build CSP (lot 3g) ; fichier `/.well-known/security.txt`.

### S9 Détection et réponse
- Supervision des erreurs, journal des échecs de connexion, alertes. Pare-feu, clés SSH uniquement, mises à jour PHP/OS/MySQL, `composer audit` planifié.
- Plan d'incident écrit : rotation `APP_KEY`, mots de passe et sessions, restauration base + storage ensemble, qui prévenir.

## 3. Méthode pour ce lot

Un sous-lot par branche. Pour chaque sous-lot : tests rouges d'abord (les tests d'absence couplés à une assertion positive), mesures, implémentation, mutations (chaque contrôle de sécurité doit pouvoir être « tué » : retirer la protection fait échouer un test), fusion, tag `stable-AAAA-MM-JJ-lotSx`.
Le S1 et le S3 changent le comportement observable (cookies, mots de passe) : à annoncer avant, à tester sur une copie, pas directement en production.
Limite : cette liste réduit le risque, elle ne l'annule pas. Un test d'intrusion externe avant la mise en production reste recommandé.
