#!/bin/bash

# Script exécuté automatiquement par Jenkins après le déploiement.
# Si vous n’avez rien à exécuter ici, merci de laisser ce fichier en place avec ce message.

echo ""
echo "========== post-deploy.sh =========="
echo "✅ Script post-deploy.sh exécuté."
echo "⚠️  Merci de ne pas supprimer ce fichier, même s’il est vide."
echo "==================================="
echo ""

# Paramètre attendu : prod ou preprod
ENV=$1

if [ "$ENV" == "prod" ]; then
  USER="collection"
elif [ "$ENV" == "preprod" ]; then
  USER="collection-pp"
else
  echo "Usage: $0 [prod|preprod]"
  exit 1
fi

echo "Commande de migration pour $ENV..."
cd "$(readlink -f /home/$USER/www)" || { echo "Répertoire introuvable"; exit 1; }

php artisan migrate
php artisan storage:link

echo "🔓 Suppression du mode lecture seule sur tous les index Elasticsearch..."
curl -XPUT -H "Content-Type: application/json" http://127.0.0.1:9200/_all/_settings -d '{"index.blocks.read_only_allow_delete": null}'

# php artisan migrate
# php artisan db:seed --class=UpdateDatabaseSeeder

