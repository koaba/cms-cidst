# Sauvegardes, copie miroir et restauration

Page : Administration > Sauvegardes (Super Admin seulement). Rien ne tourne dans une requete web :
l'administration ecrit une demande, le planificateur Laravel la traite (cron `schedule:run` chaque minute
en production ; sous Laragon : `php artisan schedule:work`).

## Les trois protections

| Protection | Contenu | Frequence | Ou |
|---|---|---|---|
| Sauvegarde (`backup:process`, `backup:auto`) | base (`base.sql.gz`) et/ou fichiers (`storage-app.tgz`) | a la demande, ou automatique (reglable) | `storage/backups/AAAAMMJJ-HHMMSS` |
| Copie miroir (`backup:sync-files`) | copie incrementale de `storage/app` | toutes les 5 minutes si l'option est activee | `storage/backups/miroir/app` |
| Sauvegarde root de l'installation | base + `storage/app`, avant chaque mise a jour | a chaque `install-serveur.sh` | `/var/backups/cidst` |

Le miroir et les archives sont sur le MEME serveur : une panne du serveur les emporte. Le bouton
« Tout telecharger » (page Sauvegardes) donne la derniere sauvegarde complete : conservez-la ailleurs.

## Copie miroir

- Activation : case « Copie miroir des fichiers » de la page Sauvegardes, puis Enregistrer.
- Elle DOUBLE l'espace utilise par `storage/app`. La premiere copie peut etre longue (tache de fond, sans
  limite de temps). Verifier l'espace libre du serveur avant de l'activer.
- Elle ne copie que les fichiers nouveaux ou modifies, et ne supprime JAMAIS rien : un fichier supprime du
  site reste dans le miroir.
- Garde d'espace : la copie est refusee si l'espace libre restant serait inferieur a `BACKUP_MIRROR_RESERVE_MB`
  (500 par defaut). L'erreur s'affiche sur la page.
- Dossier : `BACKUP_MIRROR_PATH` (defaut `storage/backups/miroir`). Il doit etre HORS de `storage/app`.
- Copie immediate : bouton « Copier maintenant » (demarre dans les 5 minutes), ou
  `php artisan backup:sync-files` apres avoir active l'option.

## Restauration des fichiers depuis le miroir

Bouton « Restaurer » (demarre dans les 5 minutes) ou, en ligne de commande :

    sudo -u www-data php artisan backup:restore-files

La restauration ne remplace JAMAIS un fichier deja present : elle ne recree que ce qui manque. Consequence :
un fichier supprime volontairement reapparait comme fichier orphelin, invisible sur le site (aucune ligne de
base ne le reference). Elle ne restaure pas la base de donnees.

## Restauration depuis un fichier telecharge (SSH)

Le navigateur ne peut pas envoyer une archive de plusieurs centaines de Mo (envoi limite a 20 Mo). Depuis
le poste, envoyer les fichiers sur le serveur (scp), puis dans la session ssh :

    cd /var/www/cidst
    sudo -u www-data php artisan down
    gunzip -c base.sql.gz | mysql cidst
    tar -xzf storage-app.tgz -C /var/www/cidst/storage
    chown -R www-data:www-data /var/www/cidst/storage/app
    sudo -u www-data php artisan cache:clear
    sudo -u www-data php artisan up

`base.sql.gz` remplace toute la base : faire d'abord une sauvegarde de l'etat actuel. L'archive contient
le dossier `app/` ; elle s'extrait donc dans `storage/`. Procedure non verifiee sur un vrai serveur.

## Limites connues

- Rien de ceci n'a ete execute sur un vrai MySQL ni un vrai serveur (tests : SQLite et dossiers temporaires).
- Pas de chiffrement des archives, pas de copie hors serveur (suites logiques : SFTP / S3 / Google Drive, age ou gpg).
- Les dumps contiennent les mots de passe hashes : acces Super Admin seulement.
