#!/bin/bash

# Script exécuté automatiquement par Jenkins après le déploiement.
# Si vous n’avez rien à exécuter ici, merci de laisser ce fichier en place avec ce message.

echo ""
echo "========== post-deploy.sh =========="
echo "✅ Script post-deploy.sh exécuté."
echo "⚠️  Merci de ne pas supprimer ce fichier, même s’il est vide."
echo "==================================="
echo ""



php artisan migrate
php artisan db:seed --class=UpdateDatabaseSeeder
