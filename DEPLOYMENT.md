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

## 🟠 Important — configuration serveur

- [ ] Base de données de production configurée (pas la base locale de dev)
- [ ] `php artisan config:cache` / `route:cache` / `view:cache` pour les perfs
- [ ] Permissions fichiers correctes (`storage/`, `bootstrap/cache/`)
- [ ] `npm run build` exécuté (assets compilés, pas de `npm run dev`)
- [ ] Sauvegardes base de données automatisées (stratégie à définir — backlog projet)

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

Session du 16/07/2026 :
- ✅ Rate limiting login vérifié (natif Breeze, `LoginRequest.php`)
- ✅ Politique de mot de passe renforcée ajoutée (`AppServiceProvider.php`)
- ✅ Mass assignment vérifié sur tous les modèles (Article, Page, Slider, NewsTicker, User)
  — tous ont un `$fillable` strict, `user_id` toujours assigné côté serveur (jamais depuis
  la requête brute)
- ✅ Headers de sécurité HTTP ajoutés et vérifiés en conditions réelles (commit 8d16e22) :
  X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy confirmés
  présents dans les réponses HTTP
- - ✅ Encodage UTF-8 vérifié le 21/07/2026 : faux positif, les fichiers sont
  correctement encodés en UTF-8. La distorsion venait de l'affichage du terminal
  cmd.exe (code page par défaut) et non du code — `chcp 65001` avant `findstr`
  confirme un affichage propre. Rien à corriger dans le code.
  (caractères mal encodés : "crÃ©Ã©" au lieu de "créé") — cosmétique, non bloquant
- 📝 `X-Powered-By: PHP/8.3.30` visible dans les headers — à masquer en prod (voir ci-dessus)

- Sous-menus (dropdown) pour le module Menus — actuellement liste à plat par choix, à ajouter quand le site aura plus de contenu (ajouter parent_id nullable + logique récursive)

## Session du 21/07/2026

- ✅ Bug bloquant résolu : ParseError Blade sur `welcome.blade.php` causé par `@php(...)`
  avec parenthèses imbriquées (`SiteSetting::current()`) — fix passé en `@php ... @endphp`.
  Voir commit `70133f7`.
- ✅ Audit complet du projet pour d'autres usages de `@php(...)` avec parenthèses
  imbriquées — aucun autre cas trouvé
  (`findstr /S /M /C:"@php(" resources\views\*.blade.php` → 0 résultat). Rien à corriger.
- ✅ Nettoyage des 4 migrations orphelines `hero_pattern_*` (fonctionnalité de motif de
  fond abandonnée, remplacée par un SVG statique) : rollback ciblé + suppression des
  fichiers de migration, sans perte de la migration `hero_eyebrow_size`. Voir méthode
  documentée dans `ENVIRONMENT.md`.
- ✅ Commit `70133f7` : fix ParseError + module `hero_eyebrow_size` (migration, modèle,
  controller, vue admin) + logo dynamique dans `MainMenu`/`main-menu.blade.php`
  (utilise désormais `SiteSetting::current()->logo_path`, avec fallback vers le logo
  par défaut).
- 📝 Aucun point de la checklist de mise en production ci-dessus n'a été traité cette
  session (APP_DEBUG, HTTPS, mots de passe, etc. — toujours en attente).

  - [ ] Mettre à jour `guzzlehttp/guzzle` vers >=7.15.1 (4 avis de sécurité medium
      détectés le 21/07/2026 via `composer audit`, liés aux redirections/cookies HTTP)