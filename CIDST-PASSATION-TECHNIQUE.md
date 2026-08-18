
---

## 📌 À reprendre en fin de projet — Audit complet + idées d'évolution

**Statut** : en attente, à la demande du porteur de projet (18/08/2026), pas urgent.

**Objectif** : faire un audit structurel complet du projet (pas seulement les modules touchés cette session), pour :
- Vérifier la cohérence architecturale entre tous les modules (validation, gestion des médias, tests) — certains modules audités cette session (Menu, Category, PdfDocument) sont désormais bien couverts ; d'autres (Sliders, NewsTicker, Diaporamas, Videos, SEO, Sitemap, Users, Settings) n'ont pas été revus avec la même rigueur.
- Repérer d'éventuels problèmes de sécurité du même type que celui trouvé le 18/08/2026 (mot de passe stocké en clair lors d'une réinitialisation admin, corrigé — voir commit `9390c46`).
- Identifier la dette technique restante (mojibake résiduel sur certains fichiers, patterns dupliqués factorisables).
- Repérer les zones sans couverture de tests.

**Méthode prévue** : partir d'un inventaire complet (`app/Models`, `app/Http/Controllers`, `app/Http/Controllers/Admin`, `resources/views/public`) avant de proposer quoi que ce soit — même discipline "auditer avant de coder" appliquée tout au long de cette session.

**Déclencheur** : le porteur de projet le redemandera explicitement, une fois prêt à y consacrer une session dédiée.