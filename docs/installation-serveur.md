# Installation simple sur un VPS Ubuntu : CMS CIDST

Procedure standard et sans cout logiciel : Ubuntu, Nginx, PHP-FPM, MySQL et Let's Encrypt sont libres. Seule la location du serveur peut etre payante (certains fournisseurs proposent des offres gratuites limitees : conditions non verifiees, a reserver aux essais). Les scripts `scripts/install-serveur.sh` et `scripts/deployer.ps1` n'ont jamais ete executes en reel [non verifie] : le premier essai se fait sur un serveur jetable.

## Ce qu'il faut avant

1. Un VPS Ubuntu 24.04 vierge, 2 Go de RAM, acces SSH en root (ou un utilisateur avec sudo).
2. Un nom de domaine dont l'enregistrement DNS (type A) pointe sur l'adresse du serveur.
3. Sur votre poste Windows 10 ou 11 : Git, Node, et le client OpenSSH (`ssh`, `scp` et `tar` sont deja inclus dans Windows).

## Deploiement en un clic depuis Windows (recommande)

1. Cle SSH, une seule fois (evite de retaper le mot de passe a chaque commande) : dans PowerShell, `ssh-keygen -t ed25519`, puis `scp $env:USERPROFILE\.ssh\id_ed25519.pub root@ADRESSE:/tmp/cle.pub`, puis `ssh root@ADRESSE "cat /tmp/cle.pub >> ~/.ssh/authorized_keys && rm /tmp/cle.pub"` (ne pas utiliser `type ... | ssh` : PowerShell ajoute des retours chariot qui invalident la cle).
2. Double-cliquer `scripts\deployer.bat`. Au premier lancement, il cree `scripts\deployer.config.json` (jamais commite) et s'arrete : remplir `Hote`, `Utilisateur`, `Domaine`, `EmailAdmin` (`Port` et `Cle` sont facultatifs), puis relancer.
3. Le script verifie que `master` est propre et alignee sur GitHub, choisit le dernier tag `stable-*` (ou `-Tag <tag>`), demande confirmation, lance `npm run build`, envoie le build et `install-serveur.sh`, puis installe ou met a jour le serveur et affiche `app:check-environment`.
4. Premier deploiement seulement :
   - renseigner `MAIL_USERNAME` et `MAIL_PASSWORD` dans `/var/www/cidst/.env` (voir `docs/deploiement.md`, section 8), puis `sudo -u www-data php /var/www/cidst/artisan config:cache` ;
   - creer l'administrateur : `ssh -t root@ADRESSE "cd /var/www/cidst && sudo -u www-data php artisan app:create-super-admin vous@exemple.mg"`.

## Installation a la main (sans le script Windows)

1. Se connecter en SSH, puis passer root : `sudo -i`.
2. Recuperer le script : `curl -fsSLO https://raw.githubusercontent.com/koaba/cms-cidst/master/scripts/install-serveur.sh`
3. Lancer : `DOMAINE=cidst.exemple.mg EMAIL_ADMIN=vous@exemple.mg bash install-serveur.sh`
4. Sur votre poste, `npm run build`, puis envoyer `public/build/` vers `/var/www/cidst/public/build/` (SFTP) et sur le serveur : `chown -R www-data:www-data /var/www/cidst/public/build`
5. Reprendre le point 4 du deploiement en un clic (courrier, administrateur), puis `sudo -u www-data php artisan app:check-environment`.

## Ce que fait install-serveur.sh

Paquets (Nginx, PHP 8.3, MySQL, poppler, Certbot, pare-feu), reglages PHP pour les images jointes, base et utilisateur MySQL (mot de passe aleatoire dans `/root/cidst-install.txt`), code du dernier tag `stable-*` (ou `TAG=`) dans `/var/www/cidst`, `composer install --no-dev`, `.env` de production pre-rempli, cle d'application, migrations, `storage:link`, caches, site Nginx, cron `schedule:run`, pare-feu (SSH et web) et certificat HTTPS. Relance, il met a jour : le `.env`, la base, la configuration Nginx et le certificat existants sont conserves.

## Regles a retenir

- Toujours lancer `artisan` avec `sudo -u www-data` : en root, des fichiers de `storage/` deviennent illisibles pour le site.
- Ne jamais changer `APP_KEY` apres la mise en ligne (sessions et hash des visiteurs de l'Audience).
- Ne jamais lancer `php artisan db:seed` en production.
- Sauvegarde avant une mise a jour : `mysqldump cidst > sauvegarde.sql` et copie de `/var/www/cidst/storage/app`.
- Si le certificat HTTPS echoue, verifier le DNS puis relancer le deploiement.
