# Guide de deploiement : CMS CIDST

Guide standard, valable pour tout hebergement PHP. Les passages marques [non verifie] n'ont jamais ete executes en reel : le code et les tests font foi.

## 1. Le principe en 5 lignes

1. Le site est une application Laravel : le serveur doit executer PHP 8.3 et pointer sur le dossier `public/`.
2. Le code vient de GitHub (branche `master`, tags `stable-*`). Le dossier `vendor/` (composer) et `public/build/` (CSS et JS compiles) ne sont PAS dans Git : il faut les produire.
3. Les secrets (mot de passe de la base, mot de passe d'application Google, APP_KEY) vivent dans le fichier `.env` du serveur, jamais dans Git.
4. Des taches planifiees (nettoyage, audience, contact) exigent un cron qui lance `php artisan schedule:run` chaque minute.
5. Apres chaque installation ou mise a jour : `php artisan app:check-environment` dit ce qui manque.

## 2. Quel hebergement choisir

| Type | Pour qui | Points d'attention |
|---|---|---|
| VPS (acces SSH complet) | Le plus simple a maitriser pour ce projet | Vous installez PHP, MySQL, poppler, un serveur web (Nginx ou Apache) |
| Mutualise cPanel | Budget serre | Le cron se configure dans l'interface ; SSH parfois absent (alors composer et artisan se lancent en local puis on envoie les fichiers) ; poppler souvent absent |
| Autre (PaaS, conteneur) | Equipes techniques | Disque souvent ephemere : les images et PDF envoyes (`storage/`) doivent aller sur un volume persistant |

Quel que soit le choix : domaine en HTTPS obligatoire (les cookies de session sont securises en production).

## 3. Prerequis du serveur

- PHP 8.3 ou plus, avec les extensions : curl, dom, fileinfo, filter, hash, iconv, json, libxml, openssl, pcre, phar, session, simplexml, tokenizer, xml, xmlwriter, zlib, gd, et pdo_mysql.
- MySQL (le projet est developpe sur MySQL ; les tests tournent en SQLite memoire).
- `memory_limit` d'au moins 128M et `max_execution_time` d'au moins 30 s (controles par `app:check-environment`).
- Pour les images jointes du formulaire de contact : `upload_max_filesize` et `post_max_size` coherents avec les photos de telephone (a regler selon vos limites).
- poppler (binaire `pdftoppm`) pour les miniatures de PDF. Sans lui, mettre `PDF_THUMBNAIL_DRIVER` a une autre valeur n'est pas verifie : preferer l'installer [non verifie].
- Composer 2 sur le serveur, OU les fichiers `vendor/` prepares en local.
- Node n'est necessaire que pour compiler le CSS et le JS (voir 4).

## 4. Preparation sur votre poste (avant chaque deploiement)

Dossier `C:\laragon\www\laravel-academy`, sur `master` propre et poussee :

1. `npm run build` : produit `public/build/` (ignore par Git, a envoyer sur le serveur).
2. Verifier que le hook de push a passe (Pint, Larastan, tests JS, Pest).
3. Noter le tag stable le plus recent, c'est la version a deployer.

## 5. Premiere installation (dans l'ordre)

Raccourci pour un VPS Ubuntu 24.04 : `scripts/install-serveur.sh` fait les points 1 a 11 ci-dessous, et `scripts/deployer.bat` le lance depuis Windows en un clic (voir `docs/installation-serveur.md`) [non verifie en reel].

1. Recuperer le code sur le serveur : `git clone https://github.com/koaba/cms-cidst`, puis `git checkout <tag stable>`.
2. `composer install --no-dev --optimize-autoloader` : installe les dependances sans les outils de test.
3. Envoyer `public/build/` (compile en 4) dans `public/build/` du serveur.
4. Copier `.env.production.example` vers `.env`, puis remplir : `APP_URL` (votre vrai domaine en https), `DB_*` (base creee vide), `MAIL_*` (voir 8), `MAIL_FROM_ADDRESS`.
5. `php artisan key:generate` : cree APP_KEY. Ne la changez plus ensuite : elle signe les sessions et le hash des visiteurs de l'Audience.
6. `php artisan migrate --force` : cree les tables.
7. Roles et premier compte administrateur : voir 7. Ne PAS lancer `php artisan db:seed` (le seeder principal cree un compte `test@example.com` et des articles de demonstration).
8. `php artisan storage:link` : relie `public/storage` aux fichiers envoyes (controle par `app:check-environment`).
9. Droits d'ecriture du serveur web sur `storage/` et `bootstrap/cache/`.
10. Cron (voir 6).
11. `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache` : accelerent le site. A refaire apres chaque changement de `.env` ou de code.
12. `php artisan app:check-environment` : tout doit etre [OK] (le planificateur ne passe au vert qu'une minute apres la mise en place du cron).

## 6. Le cron (taches planifiees)

Une seule ligne, chaque minute (VPS : `crontab -e` ; cPanel : Taches Cron) :

    * * * * * cd /chemin/du/site && php artisan schedule:run >> /dev/null 2>&1

Il declenche : `page-views:prune` (hebdomadaire), `audience:prune`, `contact:prune-images`, `contact:prune-messages` (quotidiennes) et le battement `scheduler-heartbeat` que `app:check-environment` surveille. Sans cron, le site fonctionne mais rien n'est nettoye.

## 7. Roles et premier administrateur

Sur une base vierge (apres `php artisan migrate --force`), aucun compte n'existe. Creer le premier Super Admin :

    php artisan app:create-super-admin admin@exemple.mg --name="Nom Prenom"

- Le mot de passe est demande en saisie masquee, avec confirmation. L'option `--generate` en produit un aleatoire, affiche une seule fois.
- La commande cree aussi les roles Super Admin et Publication (le seeder n'est pas lance en production).
- Le mot de passe devra etre change a la premiere connexion.
- Elle refuse de s'executer si un Super Admin existe deja : les autres comptes se creent ensuite dans l'administration (menu Utilisateurs).
- Ne PAS lancer `php artisan db:seed` en production.
- Execution reelle sur le serveur : [non verifie] (les tests passent, tag `stable-2026-11-19-createsuperadmin`).

## 8. E-mail de contact (SMTP, exemple Gmail)

1. Sur le compte Google qui enverra les messages : activer la validation en deux etapes, puis creer un mot de passe d'application (16 caracteres).
2. Dans le `.env` du SERVEUR uniquement (jamais dans Git, jamais dans un message) : `MAIL_MAILER=smtp`, `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, `MAIL_USERNAME=<adresse Gmail>`, `MAIL_PASSWORD=<mot de passe d'application>`, `MAIL_FROM_ADDRESS=<meme adresse ou alias autorise>`.
3. `php artisan config:clear` puis `php artisan config:cache`.
4. Premier test reel : envoyer un message depuis la page Contact du site. L'alerte « Nouveau message de contact » part vers `MAIL_FROM_ADDRESS` ; elle ne contient volontairement aucune donnee du visiteur, le message se lit dans l'administration (menu Messages). Si rien n'arrive, regarder `storage/logs` ; le message reste enregistre meme si l'envoi echoue.
5. Un autre fournisseur SMTP (domaine de l'organisation, prestataire) change seulement `MAIL_HOST`, `MAIL_PORT`, identifiants et eventuellement `MAIL_SCHEME`. L'envoi reel n'a jamais ete eprouve [non verifie] : c'est le premier test a faire.

## 9. Mise a jour (deploiements suivants)

1. En local : `master` propre, hook passe, `npm run build`.
2. Sauvegarder la base (`mysqldump`) et le dossier `storage/app`.
3. Sur le serveur : `git fetch --tags`, `git checkout <nouveau tag>`.
4. `composer install --no-dev --optimize-autoloader`, envoyer le nouveau `public/build/`.
5. `php artisan migrate --force`.
6. `php artisan config:cache`, `route:cache`, `view:cache`.
7. `php artisan app:check-environment` puis parcourir le site.

Retour arriere : `git checkout <ancien tag>`, restaurer la base si une migration a change des donnees.

## 10. Verification apres mise en ligne

- `/`, un article, une page, `/contact`, `/sitemap.xml`, `/robots.txt`.
- Un ancien slug de page renomme repond en 301 vers le nouveau.
- Connexion a `/admin` : Super Admin puis Publication (menus reserves caches pour Publication).
- Favicon sur `/`, `/login`, `/admin`, `/profile` (Ctrl+F5 : cache du navigateur).
- Un vrai message de contact, et son alerte e-mail.

## 11. Decisions prises le 19/11/2026

- Fuseau : `Indian/Antananarivo` (fuseau du projet, meme decalage UTC+3 que Nairobi).
- Couleurs de marque : `#C41E2A` / `#17181A`, deja appliquees dans le code. Mesure sur le logo : le rouge dominant est environ `#DC1C24`, `#C41E2A` en est le plus proche et donne un contraste de 5,89 avec le texte blanc (`#E80000` : 4,74).