# DEPLOYMENT.md — À faire IMPÉRATIVEMENT avant la mise en production

Ce fichier liste les actions critiques de sécurité et de configuration à effectuer
**au moment du déploiement**, et pas avant (certaines cassent le confort de dev en local).

⚠️ Ne pas ouvrir le site au public tant que cette liste n'est pas cochée.

---

## 🔴 Critique — sécurité

- [ ] **APP_DEBUG=false** dans `.env`
  Actuellement `true` en local (nécessaire pour voir les stack traces pendant le dev).
  En production, laisser `true` expose l'arborescence du serveur, les requêtes SQL,
  parfois des données sensibles à n'importe quel visiteur qui tombe sur une erreur.

- [ ] **HTTPS activé** (certificat SSL/TLS, ex: Let's Encrypt)
  Le site gère des logins, des sessions admin (Super Admin), des cookies
  (`laravel-session`, `XSRF-TOKEN`). Sans HTTPS, tout ça circule en clair.
  → Ajouter aussi dans `.env` : `APP_URL=https://tondomaine.com`
  → Forcer la redirection HTTP → HTTPS côté serveur (Apache/Nginx) ou middleware Laravel

- [ ] **APP_KEY** régénérée pour l'environnement de prod si besoin (`php artisan key:generate`)
  Ne jamais réutiliser la clé de dev en prod.

- [ ] **Vérifier les mots de passe des comptes existants**
  Les comptes `admin@academy.local` et `user@academy.local` ont été réinitialisés
  manuellement via Tinker pendant le dev avec des mots de passe simples
  (`nouveauMotDePasse123`). À changer pour de vrais mots de passe forts avant
  l'ouverture publique du site.

- [ ] **Mettre à jour `guzzlehttp/guzzle` vers >=7.15.1**
  4 avis de sécurité medium détectés le 21/07/2026 via `composer audit`, liés aux
  redirections/cookies HTTP. Vérifier via `composer audit` avant la mise en prod
  qu'aucune autre dépendance n'a de vulnérabilité connue.

- [ ] **`.env` jamais commité dans Git**
  Vérifier `.gitignore`, et que `.env.example` ne contient aucune vraie valeur
  sensible (clés API, mots de passe).

## 🟠 Important — configuration serveur

- [ ] Base de données de production configurée (pas la base locale de dev)
- [ ] `php artisan config:cache` / `route:cache` / `view:cache` pour les perfs
- [ ] Permissions fichiers correctes (`storage/`, `bootstrap/cache/`)
- [ ] `npm run build` exécuté (assets compilés, pas de `npm run dev`)
- [ ] Sauvegardes base de données automatisées (stratégie à définir — backlog projet)
- [ ] Sauvegardes des fichiers uploadés (`storage/app/public`, notamment la médiathèque)
      — pas seulement la base de données
- [ ] `LOG_LEVEL` ajusté pour la prod (éviter un `laravel.log` qui grossit sans rotation)

## 🟡 À considérer

- [ ] Masquer `X-Powered-By: PHP/8.3.30` (visible dans les headers HTTP actuellement)
      → configurer `expose_php = Off` dans `php.ini` en production. Révèle la version
      exacte de PHP à tout visiteur, utilisable pour cibler des failles connues.
- [ ] Content-Security-Policy (CSP) — non traité pour l'instant, à évaluer si le site
      charge des scripts/styles externes (CDN, etc.)
- [x] Headers de sécurité HTTP de base — FAIT (X-Frame-Options, X-Content-Type-Options,
      Referrer-Policy, Permissions-Policy) via `app/Http/Middleware/SecurityHeaders.php`,
      appliqué globalement sur le groupe `web` dans `bootstrap/app.php`
- [x] Rate limiting login — déjà natif via Breeze (5 tentatives / email+IP), rien à faire
- [x] Politique de mot de passe renforcée — déjà configurée dans `AppServiceProvider.php`,
      s'active automatiquement en prod via `$this->app->isProduction()`

---

## Historique des vérifications sécurité (dev)

### Session du 16/07/2026
- ✅ Rate limiting login vérifié (natif Breeze, `LoginRequest.php`)
- ✅ Politique de mot de passe renforcée ajoutée (`AppServiceProvider.php`)
- ✅ Mass assignment vérifié sur tous les modèles (Article, Page, Slider, NewsTicker, User)
  — tous ont un `$fillable` strict, `user_id` toujours assigné côté serveur (jamais depuis
  la requête brute)
- ✅ Headers de sécurité HTTP ajoutés et vérifiés en conditions réelles (commit 8d16e22) :
  X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy confirmés
  présents dans les réponses HTTP
- ✅ Encodage UTF-8 vérifié le 21/07/2026 : faux positif, les fichiers sont
  correctement encodés en UTF-8. La distorsion venait de l'affichage du terminal
  cmd.exe (code page par défaut) et non du code — `chcp 65001` avant `findstr`
  confirme un affichage propre. Rien à corriger dans le code.
  (caractères mal encodés à l'affichage : "crÃ©Ã©" au lieu de "créé") — cosmétique, non bloquant
- 📝 `X-Powered-By: PHP/8.3.30` visible dans les headers — à masquer en prod (voir ci-dessus)

### Session du 27/07/2026
- ✅ Validation `categories` ajoutée dans `ArticleController::store()` et `update()`
  (`exists:categories,id`) — empêche l'insertion de pivots avec des IDs invalides.
  Couvert par 3 tests Pest (`ArticleCategoryValidationTest`).
- ✅ Bug réel trouvé et corrigé : `ArticleController::destroy()` ne supprimait que le
  pivot (`detach()`), laissant les entrées `media` et fichiers physiques orphelins.
  Fix avec garde-fou par compteur de références (`mediables()->count() <= 1`) pour
  anticiper la future médiathèque partagée. Couvert par 2 tests Pest
  (`ArticleMediaDeletionTest`).
- ✅ Comportement `gallery_display` (grille/diaporama) vérifié et verrouillé par
  3 tests Pest (`ArticleGalleryDisplayTest`).
- ✅ Filtrage public `published_at` verrouillé par 4 tests Pest
  (`ArticlePublicVisibilityTest`).
- ✅ Re-confirmation : encodage UTF-8 des fichiers toujours correct — `findstr`
  reste peu fiable pour juger de l'encodage sur ce projet, préférer `Select-String`
  (PowerShell) ou une vérification hex via Tinker en cas de doute.
- 📝 Aucun point de la checklist de mise en production ci-dessus n'a été traité cette
  session (APP_DEBUG, HTTPS, mots de passe, etc. — toujours en attente).

### Notes de développement fonctionnel (hors périmètre déploiement)
Voir handoff technique projet / changelog séparé pour : fix ParseError Blade
(`@php(...)` imbriqué, commit `70133f7`), nettoyage migrations `hero_pattern_*`,
logo dynamique `SiteSetting::current()`, sous-menus (dropdown) module Menus à
prévoir (`parent_id` nullable + logique récursive).