# CMS Laravel Générique — Note technique de référence
*Document de préparation, phase future — ne pas démarrer maintenant*

---

## 1. Architecture des données — recommandation centrale

### 1.1 Médiathèque : compteur de références obligatoire dès la conception
Leçon tirée de CIDST : une relation polymorphe partagée (`media` / `mediables`) sans
compteur de références casse silencieusement dès qu'un fichier est utilisé à plusieurs
endroits — suppression en cascade non voulue.

**Recommandation** : dès la Phase 2 (Articles/Pages/Médias), encapsuler la logique de
suppression dans le modèle `Media` lui-même (pas dans chaque contrôleur) :

```php
public function deleteIfOrphan(): bool
{
    if ($this->mediables()->count() === 0) {
        return $this->delete(); // déclenche l'event physical delete
    }
    return false;
}
```

Considérer `spatie/laravel-medialibrary` plutôt qu'une implémentation maison — package
mature, gère déjà conversions d'images, responsive images, disques multiples,
et évite de réinventer ce chantier pour chaque module (Articles, Pages, Sliders...).

### 1.2 Éviter la duplication logique de slug
Le générateur de slug (`static::creating` avec boucle `while exists()`) est dupliqué
identique dans `Article` et `Page`. Dès Phase 1 : extraire en trait réutilisable
(`HasSlug`) ou service dédié — la logique unique servira à tous les modules à contenu
slugifiable (Articles, Pages, Catégories, futurs modules).

### 1.3 `published_at` — pattern de visibilité générique
Le piège rencontré (`NULL <= now()` toujours faux, publication invisible sans erreur)
doit être centralisé dans un **scope Eloquent réutilisable**, pas réécrit par module :

```php
// trait HasPublicVisibility
public function scopePubliclyVisible($query)
{
    return $query->where('is_published', true)
                 ->whereNotNull('published_at')
                 ->where('published_at', '<=', now());
}
```

Appliquer ce trait à Articles, Pages, et tout futur contenu programmable — élimine
la classe de bug la plus sournoise rencontrée cette session (aucune erreur levée,
juste un contenu invisible).

---

## 2. Sécurité — au-delà de la liste fournie

Additions concrètes à la liste déjà solide du document source :

- **Validation systématique des relations pivot** (`sync`, `attach`) : toute liste
  d'IDs entrante doit passer par `exists:table,id` dans un `FormRequest` dédié — jamais
  de validation inline dans le contrôleur (dette rencontrée et corrigée sur CIDST).
- **FormRequest dédiés par module**, pas de `$request->validate()` inline — centralise
  les règles, autorise réutilisation, et sépare autorisation (`authorize()`) de
  validation.
- **2FA (authentification à deux facteurs)** pour les comptes Super Admin — absent de
  la liste fournie, standard attendu sur un CMS destiné à des administrations/universités.
- **Scan de dépendances automatisé** (`composer audit` en CI, Dependabot/Renovate) —
  la faille Guzzle détectée manuellement sur CIDST aurait dû être un check automatique,
  pas une découverte a posteriori.
- **Rotation programmée de `APP_KEY`** documentée comme procédure (pas juste au setup
  initial).
- **Politique de suppression RGPD-friendly** si déploiement en Europe/administrations :
  export des données utilisateur, suppression sur demande.

---

## 3. Qualité de code — élargir la liste fournie

En complément de Debugbar/Pint/PHPStan/Larastan/Telescope :

- **CI/CD dès Phase 1** (GitHub Actions ou GitLab CI) exécutant : Pint (format),
  Larastan niveau ≥ 5, suite Pest complète, `composer audit` — à chaque push, pas
  seulement en local. Cette session a montré la valeur des tests Pest écrits
  *après-coup* ; les écrire dans le même commit que le code qu'ils couvrent, dès
  Phase 1, coûte moins cher que de les rattraper plus tard.
- **Convention de commits sémantiques** (`feat:`, `fix:`, `refactor:`, `docs:`,
  `test:`) — déjà appliquée informellement cette session, à formaliser avec
  `commitlint` ou équivalent pour générer un changelog automatique.
- **Pest, pas seulement PHPUnit brut** : confirmé adapté au projet, prévoir dès
  Phase 1 une structure `tests/Feature` par module avec au minimum : visibilité
  publique, validation des entrées sensibles, suppression en cascade des fichiers.

---

## 4. Environnement Windows/Laragon — dette à anticiper

Points rencontrés cette session, à documenter dans un `ENVIRONMENT.md` du futur
projet dès Phase 1, pas en réaction :

- **Ne jamais faire confiance à `findstr`** pour juger de l'encodage UTF-8 — utiliser
  `Select-String` (PowerShell) ou une vérification hex via Tinker.
- **`type` en PowerShell peut afficher un contenu périmé/incorrect** dans certains cas
  (cache, synchronisation) — pour toute vérification critique avant action
  irréversible (migration destructive, suppression), toujours confirmer via
  `file_get_contents()` en PHP plutôt que via une commande shell Windows.
- **Toute commande PHP doit être exécutée dans `php artisan tinker`**, jamais
  directement dans PowerShell/cmd (confusion récurrente cette session) — envisager
  un alias ou un script `.ps1` wrapper pour les développeurs non-experts du projet.
- **Verrous de fichiers Windows** (antivirus, VPN, indexation) peuvent bloquer des
  dossiers de test générés dynamiquement (`storage/framework/testing`) — documenter
  la procédure de déblocage (fermeture des process, ou dernier recours redémarrage)
  plutôt que de la redécouvrir à chaque fois.
- **Confirmation verbale ≠ sauvegarde disque réelle** : règle générale à intégrer au
  processus de toute session de développement guidé, quel que soit l'outil (édition
  manuelle, agent IA, copier-coller) — toujours revérifier par lecture indépendante
  avant de considérer un fichier finalisé.

---

## 5. Ajouts recommandés à la feuille de route (non présents dans le document source)

| Ajout proposé | Phase suggérée | Justification |
|---|---|---|
| `spatie/laravel-medialibrary` (évaluer vs solution maison) | 2 | Évite de refaire le travail de compteur de références/conversions déjà fait par un package mature |
| Traits génériques `HasSlug`, `HasPublicVisibility` | 1 | Élimine duplication déjà observée entre 2 modules seulement |
| CI/CD (lint + tests + audit sécurité) | 1 | Moins cher en Phase 1 qu'en rattrapage Phase 4 |
| 2FA Super Admin | 4 (Sécurité) | Standard attendu en contexte institutionnel |
| Sitemap XML + `robots.txt` dynamique | 3 (SEO) | Absent de la liste SEO fournie, basique attendu |
| Données structurées Schema.org (Article, WebPage) | 3 (SEO) | Améliore l'indexation, coût d'implémentation faible |
| Accessibilité WCAG AA (contrastes, ARIA, navigation clavier) | 3 (Thèmes) | Critère souvent obligatoire pour marchés publics/universités |
| i18n / multi-langue (structure, même si 1 seule langue au départ) | 1 (fondations) | Coûteux à ajouter rétroactivement, presque gratuit si prévu dès les migrations (`locale` sur le contenu) |
| Endpoint de health-check standard (`/up` Laravel natif ou `/api/health`) | 4 | Nécessaire pour supervision externe (uptime monitoring) |
| Logs structurés (JSON) en option pour Mode Enterprise | 4/5 | Facilite l'ingestion par un futur outil de supervision centralisé |
| Chiffrement des sauvegardes at-rest | 4 | Basique de sécurité absent de la liste fournie |
| Docker Compose de référence (dev + prod) | 5 | Complète la compatibilité hébergement, accélère l'onboarding développeur |

---

## 6. Ce qui est déjà solide dans le document source (à ne pas modifier)

- La séparation modes Lite/Standard/Enterprise avec détection automatique est une
  bonne architecture, cohérente avec la contrainte "vieux serveur → serveur récent".
- La séparation stricte outils de dev (Debugbar, Telescope) vs production est
  la bonne pratique standard, à maintenir.
- Le séquencement des phases est logique (fondations → contenu → présentation →
  sécurité/maintenance → optimisation) et n'a pas besoin d'être réordonné.

---

## 7. Principe directeur pour la suite du projet CIDST

Consigne déjà actée et confirmée pertinente par cette session : **ne pas
sur-architecturer CIDST maintenant**. Les patterns identifiés ci-dessus (traits
génériques, compteur de références, scopes de visibilité) peuvent être introduits
progressivement dans CIDST au fil de l'eau *si l'occasion se présente naturellement*
(comme le fix du garde-fou média fait aujourd'hui), sans forcer une refonte
prématurée. La réévaluation complète reste prévue en fin de projet CIDST, comme
décidé le 23/07/2026.
