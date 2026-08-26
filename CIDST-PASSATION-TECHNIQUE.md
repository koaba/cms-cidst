
---

## ðŸ“Œ Ã€ reprendre en fin de projet â€” Audit complet + idÃ©es d'Ã©volution

**Statut** : en attente, Ã  la demande du porteur de projet (18/08/2026), pas urgent.

**Objectif** : faire un audit structurel complet du projet (pas seulement les modules touchÃ©s cette session), pour :
- VÃ©rifier la cohÃ©rence architecturale entre tous les modules (validation, gestion des mÃ©dias, tests) â€” certains modules auditÃ©s cette session (Menu, Category, PdfDocument) sont dÃ©sormais bien couverts ; d'autres (Sliders, NewsTicker, Diaporamas, Videos, SEO, Sitemap, Users, Settings) n'ont pas Ã©tÃ© revus avec la mÃªme rigueur.
- RepÃ©rer d'Ã©ventuels problÃ¨mes de sÃ©curitÃ© du mÃªme type que celui trouvÃ© le 18/08/2026 (mot de passe stockÃ© en clair lors d'une rÃ©initialisation admin, corrigÃ© â€” voir commit `9390c46`).
- Identifier la dette technique restante (mojibake rÃ©siduel sur certains fichiers, patterns dupliquÃ©s factorisables).
- RepÃ©rer les zones sans couverture de tests.

**MÃ©thode prÃ©vue** : partir d'un inventaire complet (`app/Models`, `app/Http/Controllers`, `app/Http/Controllers/Admin`, `resources/views/public`) avant de proposer quoi que ce soit â€” mÃªme discipline "auditer avant de coder" appliquÃ©e tout au long de cette session.

**DÃ©clencheur** : le porteur de projet le redemandera explicitement, une fois prÃªt Ã  y consacrer une session dÃ©diÃ©e.
---
## ðŸ“Œ Ã€ reprendre en fin de projet â€” Filigrane : miniatures propres + vidÃ©o (25/08/2026)
**Statut** : en attente, discutÃ© en session mais pas implÃ©mentÃ©.
**Demande du porteur de projet** : le filigrane ne doit plus Ãªtre visible sur les 
miniatures (images, PDF) ni sur les vidÃ©os â€” il doit apparaÃ®tre uniquement au clic 
pour voir en grand, ou au tÃ©lÃ©chargement.
**Constat technique** : impossible avec l'architecture actuelle (WatermarkService 
applique le filigrane une fois, Ã  l'upload, directement sur le fichier stockÃ© â€” 
il n'existe qu'une seule version du fichier, donc la miniature gÃ©nÃ©rÃ©e Ã  partir 
de lui est nÃ©cessairement filigranÃ©e).
**Deux options Ã  trancher ensemble avant implÃ©mentation** :
- Option A (recommandÃ©e) : filigrane Ã  la volÃ©e. On stocke uniquement l'original 
  propre ; une route dÃ©diÃ©e applique le filigrane au moment de la vue/tÃ©lÃ©chargement, 
  avec mise en cache. Un seul fichier source, plus flexible si le style du filigrane 
  change un jour.
- Option B : deux fichiers stockÃ©s (original + filigranÃ©) dÃ¨s l'upload, comme 
  aujourd'hui mais en gardant l'original en plus. Plus simple Ã  servir, mais double 
  l'espace disque utilisÃ©.
**VidÃ©o** : WatermarkService n'a actuellement aucune mÃ©thode pour les vidÃ©os. 
Filigraner une vidÃ©o (incrustation FFmpeg typiquement) est un chantier technique 
distinct, plus lourd en traitement â€” Ã  traiter sÃ©parÃ©ment du filigrane images/PDF, 
pas dans le mÃªme lot.
**DÃ©clencheur** : Ã  rediscuter en fin de projet avec le porteur de projet.
---
---
## ðŸ“Œ Ã€ reprendre en fin de projet â€” Zones de dÃ©pÃ´t drag & drop (25/08/2026)
**Demande** : ajouter le glisser-dÃ©poser sur toutes les zones d'upload de fichiers 
(galerie images, PDF articles, PDF documents autonomes, diaporamas, vidÃ©os), en 
complÃ©ment du bouton "Parcourir" classique (pas en remplacement).
**Contrainte d'architecture Ã  respecter** : un seul module JS gÃ©nÃ©rique et rÃ©utilisable 
(mÃªme principe que pdf-thumbnail.js â€” pilotÃ© par data-attributes, attachÃ© une fois 
via querySelectorAll), jamais une implÃ©mentation dupliquÃ©e par type de mÃ©dia ou par vue.
**Pourquoi pas maintenant** : chantier transversal touchant plusieurs vues Ã  la fois, 
mieux traitÃ© dans une session dÃ©diÃ©e avec le temps de vÃ©rifier chaque zone individuellement, 
plutÃ´t qu'en fin de session de nettoyage.
---
---
## 📌 À reprendre en fin de projet — Filigrane : miniatures propres + vidéo (25/08/2026)
**Statut** : en attente, discuté en session mais pas implémenté.
**Demande du porteur de projet** : le filigrane ne doit plus être visible sur les 
miniatures (images, PDF) ni sur les vidéos — il doit apparaître uniquement au clic 
pour voir en grand, ou au téléchargement.
**Constat technique** : impossible avec l'architecture actuelle (WatermarkService 
applique le filigrane une fois, à l'upload, directement sur le fichier stocké — 
il n'existe qu'une seule version du fichier, donc la miniature générée à partir 
de lui est nécessairement filigranée).
**Deux options à trancher ensemble avant implémentation** :
- Option A (recommandée) : filigrane à la volée. On stocke uniquement l'original 
  propre ; une route dédiée applique le filigrane au moment de la vue/téléchargement, 
  avec mise en cache. Un seul fichier source, plus flexible si le style du filigrane 
  change un jour.
- Option B : deux fichiers stockés (original + filigrané) dès l'upload, comme 
  aujourd'hui mais en gardant l'original en plus. Plus simple à servir, mais double 
  l'espace disque utilisé.
**Vidéo** : WatermarkService n'a actuellement aucune méthode pour les vidéos. 
Filigraner une vidéo (incrustation FFmpeg typiquement) est un chantier technique 
distinct, plus lourd en traitement — à traiter séparément du filigrane images/PDF, 
pas dans le même lot.
**Déclencheur** : à rediscuter en fin de projet avec le porteur de projet.

---
## 📌 À reprendre en fin de projet — Zones de dépôt drag & drop (25/08/2026)
**Demande** : ajouter le glisser-déposer sur toutes les zones d'upload de fichiers 
(galerie images, PDF articles, PDF documents autonomes, diaporamas, vidéos), en 
complément du bouton "Parcourir" classique (pas en remplacement).
**Contrainte d'architecture à respecter** : un seul module JS générique et réutilisable 
(même principe que pdf-thumbnail.js — piloté par data-attributes, attaché une fois 
via querySelectorAll), jamais une implémentation dupliquée par type de média ou par vue.
**Pourquoi pas maintenant** : chantier transversal touchant plusieurs vues à la fois, 
mieux traité dans une session dédiée avec le temps de vérifier chaque zone individuellement, 
plutôt qu'en fin de session de nettoyage.
**Déclencheur** : à rediscuter en fin de projet avec le porteur de projet.