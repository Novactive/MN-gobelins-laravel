
### **Procédure de Synchronisation**

#### **1. Synchronisation complète**

Pour effectuer une synchronisation complète, suivez les étapes ci-dessous :

1. **Supprimer le fichier offset :**

   ```bash
   rm -rf /home/collection-pp/releases/script/offset.txt
   ```

2. **Activer le cron pour le remplissage de la queue :**

   ```bash
   * * * * * /usr/bin/flock -n /tmp/import_zetcom.lock /bin/bash /home/collection-pp/releases/script/import_zetcom.sh 10
   ```

3. **Désactiver temporairement les deux crons suivants** jusqu’à ce que la synchronisation soit entièrement terminée :

   ```bash
   #0 2 * * * cd /home/collection-pp/www && /usr/local/bin/php artisan gobelins:import:zetcom
   #0 3 * * * cd /home/collection-pp/www && /usr/local/bin/php artisan gobelins:import:related >> /home/collection-pp/www/storage/logs/import_related_object.log 2>&1
   ```

---

#### **2. Accélération du traitement de la queue**

Pour **accélérer la consommation de la queue**, vous pouvez activer plusieurs crons supplémentaires (jusqu’à **6 processus maximum**) afin d’éviter de surcharger le serveur :

```bash
0 0 * * * flock -n /tmp/queue_worker_1.lock nohup /usr/local/bin/php /home/collection-pp/www/artisan queue:listen rabbitmq --timeout=300 --tries=3 >> /home/collection-pp/www/storage/logs/worker_$(date +\%d_\%m_\%Y).log 2>&1
...
...
0 0 * * * flock -n /tmp/queue_worker_n.lock nohup /usr/local/bin/php /home/collection-pp/www/artisan queue:listen rabbitmq --timeout=300 --tries=3 >> /home/collection-pp/www/storage/logs/worker_$(date +\%d_\%m_\%Y).log 2>&1
```

---

#### **3. Synchronisation d’objets spécifiques**

Pour synchroniser uniquement certains objets, passez leurs **IDs** en paramètre exemple dans la commande suivante :

```bash
php artisan gobelins:import:zetcom --all --limit=5 --offset=0 --objectIds=48070,48069
```

---

#### **4. Action manuelle après chaque déploiement**

Après certaines livraisons, il arrive que **le menu de gauche ne s’affiche pas** lors du déploiement, car son chargement peut prendre du temps et bloquer l’affichage de la page d’accueil.

Ce problème est désormais **géré via AJAX**.
Si le menu ne s’affiche toujours pas, ouvrez simplement l’URL suivante dans votre navigateur pour le régénérer :
 [https://preprod-collection.mobilier-national.fr/api/filters](https://preprod-collection.mobilier-national.fr/api/filters)
