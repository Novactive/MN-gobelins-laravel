#!/bin/bash

# Script exécuté automatiquement par Jenkins avant le déploiement.
# Si vous n’avez rien à exécuter ici, merci de laisser ce fichier en place avec ce message.

echo ""
echo "========== pre-deploy.sh =========="
echo "✅ Script pre-deploy.sh exécuté."
echo "⚠️  Merci de ne pas supprimer ce fichier, même s’il est vide."
echo "==================================="
echo ""

echo "Commande de migration..."
cd $(readlink  -f /home/collection-pp/www) ; php artisan migrate

echo "🔓 Suppression du mode lecture seule sur tous les index Elasticsearch..."
curl -XPUT -H "Content-Type: application/json" http://127.0.0.1:9200/_all/_settings -d '{"index.blocks.read_only_allow_delete": null}'

